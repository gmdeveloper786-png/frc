<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\EnrollmentScheduleOccurrence;
use App\Models\ProgressNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Therapist-facing dashboard & roster queries (assigned assessments, children, sessions).
 *
 * Authorization for HTTP/API routes remains in controllers / middleware ({@see RoleMiddleware}).
 */
class TherapistPortalService
{
    public const DASHBOARD_PREVIEW_LIMIT = 5;

    /** How far back to scan for completed sessions missing finalized progress notes. */
    public const PENDING_DOCUMENTATION_LOOKBACK_DAYS = 90;

    public const PENDING_DOCUMENTATION_CACHE_MINUTES = 2;

    /** Default session list window when no start/end dates are submitted. */
    public const SESSIONS_DEFAULT_PAST_WEEKS = 4;

    public const SESSIONS_DEFAULT_FUTURE_WEEKS = 8;

    /** Wider lookback when filtering by status/child without explicit dates. */
    public const SESSIONS_STATUS_FILTER_PAST_WEEKS = 12;

    /** Occurrence expansion window for assigned-children session summaries. */
    public const ASSIGNED_CHILDREN_SESSION_PAST_WEEKS = 12;

    public const ASSIGNED_CHILDREN_SESSION_FUTURE_WEEKS = 8;

    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly ChildScheduleService $childScheduleService,
        private readonly SessionOccurrenceDetailService $occurrenceDetailService,
        private readonly SessionOccurrenceStateService $occurrenceState,
        private readonly UserNotificationService $userNotifications,
    ) {}

    public function getAssignedChildIds(int $therapistId): SupportCollection
    {
        $fromAssessments = Assessment::query()
            ->where('therapist_id', $therapistId)
            ->whereIn('status', ['publish', 'completed'])
            ->join('assessment_children', 'assessments.id', '=', 'assessment_children.assessment_id')
            ->pluck('assessment_children.child_id');

        $fromEnrollments = Enrollment::query()
            ->where('therapist_id', $therapistId)
            ->whereIn('status', ['approved', 'active'])
            ->pluck('child_id');

        $fromSchedules = EnrollmentSchedule::query()
            ->where('enrollment_schedules.therapist_id', $therapistId)
            ->whereHas('enrollment', fn($q) => $q->whereIn('status', ['approved', 'active']))
            ->join('enrollments', 'enrollment_schedules.enrollment_id', '=', 'enrollments.id')
            ->pluck('enrollments.child_id');

        return $fromAssessments->merge($fromEnrollments)->merge($fromSchedules)->unique()->filter()->values();
    }

    public function therapistHasAccessToChild(int $therapistId, int $childId): bool
    {
        return $this->getAssignedChildIds($therapistId)->containsStrict($childId);
    }

    /** Base query: schedules assigned to therapist with active enrolments. */
    public function sessionsBaseQuery(int $therapistId)
    {
        return EnrollmentSchedule::query()
            ->where('therapist_id', $therapistId)
            ->whereHas('enrollment', fn($q) => $q->whereIn('status', ['approved', 'active']));
    }

    public function dayMatchesCalendarWeekday(?string $scheduleDay, string $calendarWeekdayFull): bool
    {
        $s = strtolower(trim((string) $scheduleDay));
        $c = strtolower(trim($calendarWeekdayFull));
        if ($s === '' || $c === '') {
            return false;
        }

        return str_starts_with($c, $s) || str_starts_with($s, substr($c, 0, 3));
    }

    public function schedulesForCalendarDay(int $therapistId, Carbon $day): Collection
    {
        $dayStr = $day->toDateString();
        $dow = $day->format('l');

        return $this->sessionsBaseQuery($therapistId)
            ->with(['enrollment.child', 'enrollment.service', 'branch'])
            ->where(function ($q) use ($dayStr, $dow) {
                $q->whereDate('session_date', $dayStr)
                    ->orWhere(function ($q2) use ($dow) {
                        $q2->whereNull('session_date')
                            ->whereRaw('LOWER(TRIM(day)) LIKE ?', [strtolower(substr($dow, 0, 3)) . '%']);
                    });
            })
            ->orderBy('time_slot')
            ->get();
    }

    /**
     * @param  array{
     *     today_assessments_count?: int,
     *     today_sessions_count?: int,
     *     assessment_counts?: array{upcoming_week: int, completed: int, cancelled: int}
     * }  $context
     */
    public function dashboardStats(int $therapistId, array $context = []): array
    {
        $assessmentCounts = $context['assessment_counts']
            ?? $this->assessmentService->getTherapistDashboardAssessmentCounts($therapistId);

        $weekStart = now()->copy()->addDay()->startOfDay();
        $weekEnd = now()->copy()->addDays(7)->endOfDay();

        $upcomingSessions = $this->collectExpandedSessionsInRange($therapistId, $weekStart, $weekEnd)
            ->filter(fn (array $r): bool => in_array($r['status'], ['scheduled', 'in_progress'], true))
            ->count();

        $notesPending = $this->countPendingDocumentationOccurrences($therapistId);

        return [
            'assigned_children'     => $this->getAssignedChildIds($therapistId)->count(),
            'today_assessments'     => $context['today_assessments_count']
                ?? $this->assessmentService->getTherapistTodayAssessments($therapistId)->count(),
            'upcoming_assessments'  => $assessmentCounts['upcoming_week'],
            'completed_assessments' => $assessmentCounts['completed'],
            'today_sessions'        => $context['today_sessions_count']
                ?? $this->schedulesForCalendarDay($therapistId, now())->count(),
            'upcoming_sessions'     => $upcomingSessions,
            'completed_sessions'    => $this->countCompletedSessionOccurrences($therapistId),
            'notes_pending'         => $notesPending,
            'cancelled_sessions'    => $this->countCancelledSessionOccurrences($therapistId),
            'cancelled_assessments' => $assessmentCounts['cancelled'],
        ];
    }

    public function countPendingDocumentationOccurrences(int $therapistId): int
    {
        return $this->pendingDocumentationOccurrences($therapistId)->count();
    }

    public function forgetPendingDocumentationCache(int $therapistId): void
    {
        Cache::forget("therapist_pending_docs_rows:{$therapistId}");
    }

    public function countCompletedSessionOccurrences(int $therapistId): int
    {
        $fixed = $this->sessionsBaseQuery($therapistId)
            ->whereNotNull('session_date')
            ->where('status', 'completed')
            ->count();

        $recurring = EnrollmentScheduleOccurrence::query()
            ->where('status', 'completed')
            ->whereHas('schedule', fn ($q) => $q->where('therapist_id', $therapistId))
            ->count();

        return $fixed + $recurring;
    }

    public function countCancelledSessionOccurrences(int $therapistId): int
    {
        $fixed = $this->sessionsBaseQuery($therapistId)
            ->whereNotNull('session_date')
            ->where('status', 'cancelled')
            ->count();

        $recurring = EnrollmentScheduleOccurrence::query()
            ->where('status', 'cancelled')
            ->whereHas('schedule', fn ($q) => $q->where('therapist_id', $therapistId))
            ->count();

        return $fixed + $recurring;
    }

    /**
     * Completed session occurrences missing a finalized progress note (none or draft only).
     * Cached briefly; use {@see forgetPendingDocumentationCache} after note/session changes.
     *
     * @return SupportCollection<int, array<string,mixed>>
     */
    public function pendingDocumentationOccurrences(int $therapistId): SupportCollection
    {
        return Cache::remember(
            "therapist_pending_docs_rows:{$therapistId}",
            now()->addMinutes(self::PENDING_DOCUMENTATION_CACHE_MINUTES),
            fn (): SupportCollection => $this->buildPendingDocumentationOccurrences($therapistId),
        );
    }

    /**
     * @return SupportCollection<int, array{value: string, label: string, child_id: ?int, service_id: ?int}>
     */
    public function mapPendingRowsToOccurrencePickOptions(SupportCollection $rows): SupportCollection
    {
        return $rows->map(function (array $row): array {
            /** @var EnrollmentSchedule $sch */
            $sch = $row['schedule'];
            $serviceId = $sch->enrollment?->service_id;

            return [
                'value'      => $sch->id . '|' . $row['effective_date']->toDateString() . '|' . ($serviceId ?? ''),
                'label'      => $row['effective_date']->format('d M Y') . ' · ' . $row['child_name'] . ' · ' . $row['time_slot'] . ' · ' . $row['service_name'],
                'child_id'   => $sch->enrollment?->child_id,
                'service_id' => $serviceId,
            ];
        })->values();
    }

    public function paginatePendingDocumentationOccurrences(
        int $therapistId,
        int $perPage = 15,
        ?int $page = null,
    ): LengthAwarePaginator {
        $page = max(1, $page ?? 1);
        $all = $this->pendingDocumentationOccurrences($therapistId);

        return new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => route('therapist.progress-notes.pending')],
        );
    }

    /**
     * @return SupportCollection<int, array<string,mixed>>
     */
    private function buildPendingDocumentationOccurrences(int $therapistId): SupportCollection
    {
        [$from, $to] = $this->pendingDocumentationDateRange();
        $notesByKey = $this->loadProgressNotesIndexForPending($therapistId, $from, $to);

        return $this->collectExpandedSessionsInRange($therapistId, $from, $to)
            ->filter(function (array $r) use ($notesByKey): bool {
                if (($r['status'] ?? '') !== 'completed') {
                    return false;
                }

                /** @var Carbon $effective */
                $effective = $r['effective_date'];
                if ($effective->isFuture()) {
                    return false;
                }

                /** @var EnrollmentSchedule $sch */
                $sch = $r['schedule'];
                $key = $sch->id . '|' . $effective->toDateString();
                $notes = $notesByKey->get($key, collect());

                return ! $notes->contains(fn (ProgressNote $n): bool => $n->status === 'completed');
            })
            ->map(function (array $r) use ($notesByKey): array {
                /** @var EnrollmentSchedule $sch */
                $sch = $r['schedule'];
                /** @var Carbon $effective */
                $effective = $r['effective_date'];
                $key = $sch->id . '|' . $effective->toDateString();
                $notes = $notesByKey->get($key, collect());
                $draftPn = $notes->firstWhere('status', 'draft');
                $r['progress_note_row_status'] = $draftPn ? 'draft' : 'missing';
                $r['draft_progress_note_id'] = $draftPn?->id;

                return $r;
            })
            ->sortByDesc(fn (array $r) => $r['effective_date']->timestamp . '_' . $r['time_slot'])
            ->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function pendingDocumentationDateRange(): array
    {
        return [
            now()->copy()->subDays(self::PENDING_DOCUMENTATION_LOOKBACK_DAYS)->startOfDay(),
            now()->copy()->endOfDay(),
        ];
    }

    /**
     * @return SupportCollection<string, SupportCollection<int, ProgressNote>>
     */
    private function loadProgressNotesIndexForPending(int $therapistId, Carbon $from, Carbon $to): SupportCollection
    {
        return ProgressNote::query()
            ->where('therapist_id', $therapistId)
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $to)
            ->orderByDesc('updated_at')
            ->get(['id', 'enrollment_schedule_id', 'session_date', 'status'])
            ->groupBy(fn (ProgressNote $n): string => $n->enrollment_schedule_id . '|' . $n->session_date->toDateString());
    }

    /**
     * Validates therapist owns schedule, session is completed for template row, and occurrence exists on enrollment calendar.
     */
    public function occurrenceBelongsToTherapistCompletedSchedule(int $therapistId, int $scheduleId, string $sessionDateIso): bool
    {
        $schedule = EnrollmentSchedule::query()
            ->whereKey($scheduleId)
            ->where('therapist_id', $therapistId)
            ->whereHas('enrollment', fn($q) => $q->whereIn('status', ['approved', 'active']))
            ->first();

        if ($schedule === null) {
            return false;
        }

        try {
            $target = Carbon::parse($sessionDateIso)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        if ($this->occurrenceState->effectiveStatus($schedule, $target) !== 'completed') {
            return false;
        }

        $rows = $this->childScheduleService->getExpandedOccurrencesForEnrollmentId((int) $schedule->enrollment_id);

        return $rows->contains(function (array $row) use ($scheduleId, $target): bool {
            return (int) ($row['schedule_id'] ?? 0) === $scheduleId
                && $row['session_date']->equalTo($target);
        });
    }

    /**
     * True when this calendar occurrence exists for the therapist's schedule row (any schedule status).
     */
    public function therapistOwnsScheduleOccurrence(int $therapistId, EnrollmentSchedule $schedule, string $sessionDateIso): bool
    {
        if ((int) $schedule->therapist_id !== $therapistId) {
            return false;
        }

        $schedule->loadMissing('enrollment');
        if (! $schedule->enrollment || ! in_array($schedule->enrollment->status, ['approved', 'active'], true)) {
            return false;
        }

        try {
            $target = Carbon::parse($sessionDateIso)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        $rows = $this->childScheduleService->getExpandedOccurrencesForEnrollmentId((int) $schedule->enrollment_id);

        return $rows->contains(function (array $row) use ($schedule, $target): bool {
            return (int) ($row['schedule_id'] ?? 0) === (int) $schedule->id
                && $row['session_date']->equalTo($target);
        });
    }

    /**
     * Flatten schedules into dated rows for a calendar window.
     *
     * Uses the same recurrence rules and {@see Enrollment::total_sessions} cap as the child portal
     * ({@see ChildScheduleService::expandEnrollmentSchedules}); never emits extra weekly copies beyond billed sessions.
     *
     * @return SupportCollection<int, array<string,mixed>>
     */
    public function collectExpandedSessionsInRange(int $therapistId, Carbon $from, Carbon $to): SupportCollection
    {
        $slots = $this->sessionsBaseQuery($therapistId)
            ->with(['enrollment.child', 'enrollment.service', 'branch'])
            ->get();

        if ($slots->isEmpty()) {
            return collect();
        }

        $slotsById = $slots->keyBy('id');
        $enrollmentIds = $slots->pluck('enrollment_id')->unique()->filter()->values();

        $fromDay = $from->copy()->startOfDay();
        $toDay = $to->copy()->endOfDay();

        $out = collect();

        foreach ($enrollmentIds as $enrollmentId) {
            $rows = $this->childScheduleService->getExpandedOccurrencesForEnrollmentId((int) $enrollmentId);

            foreach ($rows as $row) {
                $scheduleId = (int) ($row['schedule_id'] ?? 0);
                if (! isset($slotsById[$scheduleId])) {
                    continue;
                }

                /** @var Carbon $sessionDate */
                $sessionDate = $row['session_date'];
                if ($sessionDate->lt($fromDay) || $sessionDate->gt($toDay)) {
                    continue;
                }

                $slot = $slotsById[$scheduleId];
                $out->push($this->expandSessionRow($slot, $sessionDate));
            }
        }

        return $this->occurrenceState->attachEffectiveStatuses(
            $out->sortBy(fn(array $r) => $r['effective_date']->timestamp . '_' . $r['time_slot'])->values(),
        );
    }

    private function expandSessionRow(EnrollmentSchedule $slot, Carbon $effectiveDate): array
    {
        return [
            'schedule'       => $slot,
            'effective_date' => $effectiveDate->copy()->startOfDay(),
            'status'         => $slot->status,
            'time_slot'      => $slot->time_slot,
            'child_name'     => $slot->enrollment?->child?->full_name ?? '—',
            'service_name'   => $slot->enrollment?->service?->name ?? '—',
            'branch_name'    => $slot->branch?->name ?? $slot->enrollment?->branch?->name ?? '—',
        ];
    }

    public function upcomingAssessmentBucketsFiltered(int $therapistId, string $filter): Collection
    {
        return match ($filter) {
            'today' => $this->assessmentService->getTherapistTodayAssessments($therapistId),
            'week' => $this->assessmentService->getTherapistUpcomingPublishedAssessments(
                $therapistId,
                now()->copy()->endOfWeek()->toDateString(),
            ),
            'month' => $this->assessmentService->getTherapistUpcomingPublishedAssessments(
                $therapistId,
                now()->copy()->endOfMonth()->toDateString(),
            ),
            default => $this->assessmentService->getTherapistUpcomingPublishedAssessments($therapistId),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveSessionFilterBounds(
        ?string $startDate,
        ?string $endDate,
        bool $includePastWhenNoDates = false,
    ): array {
        $startDate = self::normalizeFilterDate($startDate);
        $endDate = self::normalizeFilterDate($endDate);

        $today = now()->startOfDay();
        $defaultEnd = $today->copy()->addWeeks(self::SESSIONS_DEFAULT_FUTURE_WEEKS)->endOfDay();
        $defaultFrom = $includePastWhenNoDates
            ? $today->copy()->subWeeks(self::SESSIONS_STATUS_FILTER_PAST_WEEKS)->startOfDay()
            : $today->copy()->subWeeks(self::SESSIONS_DEFAULT_PAST_WEEKS)->startOfDay();

        if ($startDate === null && $endDate === null) {
            return [$defaultFrom, $defaultEnd];
        }

        $from = $startDate !== null
            ? Carbon::parse($startDate)->startOfDay()
            : ($includePastWhenNoDates
                ? $today->copy()->subWeeks(self::SESSIONS_STATUS_FILTER_PAST_WEEKS)->startOfDay()
                : $today->copy()->subWeeks(self::SESSIONS_DEFAULT_PAST_WEEKS)->startOfDay());
        $to = $endDate !== null
            ? Carbon::parse($endDate)->endOfDay()
            : $defaultEnd;

        if ($from->gt($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private static function normalizeFilterDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        return $date;
    }

    public function upcomingSessionsFiltered(
        int $therapistId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $statusFilter = null,
        ?int $childId = null,
    ): SupportCollection {
        $startDate = self::normalizeFilterDate($startDate);
        $endDate = self::normalizeFilterDate($endDate);

        $hasNonDateFilters = ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all')
            || ($childId !== null && $childId > 0);
        $includePast = $hasNonDateFilters && $startDate === null && $endDate === null;

        [$from, $to] = $this->resolveSessionFilterBounds($startDate, $endDate, $includePast);

        $rows = $this->collectExpandedSessionsInRange($therapistId, $from, $to);

        if ($statusFilter && $statusFilter !== 'all') {
            $rows = $rows->filter(fn(array $r) => $r['status'] === $statusFilter);
        }

        if ($childId !== null && $childId > 0) {
            if (! $this->therapistHasAccessToChild($therapistId, $childId)) {
                return collect();
            }
            $rows = $rows->filter(fn(array $r): bool => (int) ($r['schedule']->enrollment?->child_id) === $childId);
        }

        return $rows;
    }

    /**
     * Paginated therapist session list: filter + slice first, then batch progress notes for one page only.
     */
    public function paginateTherapistSessionsFiltered(
        int $therapistId,
        ?string $startDate,
        ?string $endDate,
        ?string $statusFilter,
        ?int $childId,
        int $perPage,
        int $page,
        string $path,
    ): LengthAwarePaginator {
        $rows = $this->upcomingSessionsFiltered($therapistId, $startDate, $endDate, $statusFilter, $childId);
        $page = max(1, $page);
        $pageRows = $rows->forPage($page, $perPage)->values();
        $metaIndex = $this->occurrenceDetailService->progressMetaIndexForSessionRows($pageRows);
        $enriched = $this->occurrenceDetailService->enrichTherapistSessionListRows($pageRows, $metaIndex);

        return new LengthAwarePaginator(
            $enriched->all(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $path],
        );
    }

    /**
     * Children the therapist may see on the session filter (assigned via enrollment, schedule, or assessment).
     *
     * @return SupportCollection<int, User>
     */
    public function childrenForSessionFilter(int $therapistId): SupportCollection
    {
        $ids = $this->getAssignedChildIds($therapistId);
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->children()
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function assignedChildrenRows(int $therapistId): SupportCollection
    {
        $ids = $this->getAssignedChildIds($therapistId);
        if ($ids->isEmpty()) {
            return collect();
        }

        $children = User::query()
            ->children()
            ->with([
                'disabilities',
                'enrollments' => fn($q) => $q->where('therapist_id', $therapistId)->with(['service', 'branch']),
            ])
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->get();

        $childIds = $children->pluck('id')->map(fn($id) => (int) $id)->all();
        $lastAssessmentByChild = $this->latestPublishedAssessmentsByChild($therapistId, $childIds);
        $sessionSummaries = $this->sessionSummariesByChild($therapistId, $childIds);

        return $children->map(
            fn(User $child) => $this->mapAssignedChildRow($child, $lastAssessmentByChild, $sessionSummaries),
        );
    }

    /**
     * @param  array<int, Assessment>  $lastAssessmentByChild
     * @param  array<int, array{last_session_date: ?string, next_session: ?EnrollmentSchedule}>  $sessionSummaries
     * @return array<string, mixed>
     */
    private function mapAssignedChildRow(User $child, array $lastAssessmentByChild, array $sessionSummaries): array
    {
        $childId = (int) $child->id;
        $primaryEnrollment = $child->enrollments->first();
        $summary = $sessionSummaries[$childId] ?? ['last_session_date' => null, 'next_session' => null];

        return [
            'child'             => $child,
            'last_assessment'   => $lastAssessmentByChild[$childId] ?? null,
            'last_session_date' => $summary['last_session_date'],
            'next_session'      => $summary['next_session'],
            'enrollment_status' => $primaryEnrollment?->status ?? '—',
            'services_label'    => $primaryEnrollment?->service?->name ?? '—',
            'branch_name'       => $primaryEnrollment?->branch?->name ?? '—',
        ];
    }

    public function paginateAssignedChildren(int $therapistId, int $perPage, int $page, string $path): LengthAwarePaginator
    {
        $ids = $this->getAssignedChildIds($therapistId);
        if ($ids->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage, max(1, $page), ['path' => $path]);
        }

        $paginator = User::query()
            ->children()
            ->with([
                'disabilities',
                'enrollments' => fn($q) => $q->where('therapist_id', $therapistId)->with(['service', 'branch']),
            ])
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->paginate($perPage, ['*'], 'page', $page);

        $childIds = $paginator->getCollection()->pluck('id')->map(fn($id) => (int) $id)->all();
        if ($childIds === []) {
            return $paginator;
        }

        $lastAssessmentByChild = $this->latestPublishedAssessmentsByChild($therapistId, $childIds);
        $sessionSummaries = $this->sessionSummariesByChild($therapistId, $childIds);

        $rows = $paginator->getCollection()->map(
            fn(User $child) => $this->mapAssignedChildRow($child, $lastAssessmentByChild, $sessionSummaries),
        );

        $paginator->setCollection($rows);

        return $paginator;
    }

    /**
     * @param  list<int>  $childIds
     * @return array<int, Assessment>
     */
    private function latestPublishedAssessmentsByChild(int $therapistId, array $childIds): array
    {
        if ($childIds === []) {
            return [];
        }

        $assessments = Assessment::query()
            ->where('therapist_id', $therapistId)
            ->whereIn('status', ['publish', 'completed'])
            ->whereHas('children', fn($q) => $q->whereIn('users.id', $childIds))
            ->with(['children' => fn($q) => $q->whereIn('users.id', $childIds)->select('users.id')])
            ->orderByDesc('date')
            ->get();

        $map = [];
        foreach ($assessments as $assessment) {
            foreach ($assessment->children as $child) {
                $cid = (int) $child->id;
                if (! isset($map[$cid])) {
                    $map[$cid] = $assessment;
                }
            }
        }

        return $map;
    }

    /**
     * Last completed and next upcoming session per child (occurrence-aware, bounded window).
     *
     * @param  list<int>  $childIds
     * @return array<int, array{last_session_date: ?string, next_session: ?EnrollmentSchedule}>
     */
    private function sessionSummariesByChild(int $therapistId, array $childIds): array
    {
        if ($childIds === []) {
            return [];
        }

        $today = now()->startOfDay();
        $from = $today->copy()->subWeeks(self::ASSIGNED_CHILDREN_SESSION_PAST_WEEKS);
        $to = $today->copy()->addWeeks(self::ASSIGNED_CHILDREN_SESSION_FUTURE_WEEKS)->endOfDay();

        $expanded = $this->collectExpandedSessionsInRangeForChildren($therapistId, $from, $to, $childIds);

        $lastByChild = [];
        $nextByChild = [];

        foreach ($expanded as $row) {
            $childId = (int) ($row['schedule']->enrollment?->child_id ?? 0);
            if ($childId === 0) {
                continue;
            }

            /** @var Carbon $occ */
            $occ = $row['effective_date'];
            $status = (string) ($row['status'] ?? '');

            if ($status === 'completed' && $occ->lte($today)) {
                if (! isset($lastByChild[$childId]) || $occ->gt($lastByChild[$childId]['date'])) {
                    $lastByChild[$childId] = ['date' => $occ->copy(), 'schedule' => $row['schedule']];
                }
            }

            if (in_array($status, ['scheduled', 'in_progress'], true) && $occ->gte($today)) {
                if (! isset($nextByChild[$childId]) || $occ->lt($nextByChild[$childId]['date'])) {
                    $nextByChild[$childId] = ['date' => $occ->copy(), 'schedule' => $row['schedule']];
                }
            }
        }

        $out = [];
        foreach ($childIds as $childId) {
            $lastDate = isset($lastByChild[$childId])
                ? $lastByChild[$childId]['date']->toDateString()
                : null;

            $nextSession = null;
            if (isset($nextByChild[$childId])) {
                $nextSession = clone $nextByChild[$childId]['schedule'];
                $nextSession->setAttribute('session_date', $nextByChild[$childId]['date']);
            }

            $out[$childId] = [
                'last_session_date' => $lastDate,
                'next_session'      => $nextSession,
            ];
        }

        return $out;
    }

    /**
     * @param  list<int>  $childIds
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function collectExpandedSessionsInRangeForChildren(
        int $therapistId,
        Carbon $from,
        Carbon $to,
        array $childIds,
    ): SupportCollection {
        if ($childIds === []) {
            return collect();
        }

        $slots = $this->sessionsBaseQuery($therapistId)
            ->with(['enrollment.child', 'enrollment.service', 'branch'])
            ->whereHas('enrollment', fn($q) => $q->whereIn('child_id', $childIds))
            ->get();

        if ($slots->isEmpty()) {
            return collect();
        }

        $slotsById = $slots->keyBy('id');
        $enrollmentIds = $slots->pluck('enrollment_id')->unique()->filter()->values();

        $fromDay = $from->copy()->startOfDay();
        $toDay = $to->copy()->endOfDay();

        $out = collect();

        foreach ($enrollmentIds as $enrollmentId) {
            $rows = $this->childScheduleService->getExpandedOccurrencesForEnrollmentId((int) $enrollmentId);

            foreach ($rows as $row) {
                $scheduleId = (int) ($row['schedule_id'] ?? 0);
                if (! isset($slotsById[$scheduleId])) {
                    continue;
                }

                /** @var Carbon $sessionDate */
                $sessionDate = $row['session_date'];
                if ($sessionDate->lt($fromDay) || $sessionDate->gt($toDay)) {
                    continue;
                }

                $slot = $slotsById[$scheduleId];
                $out->push($this->expandSessionRow($slot, $sessionDate));
            }
        }

        return $this->occurrenceState->attachEffectiveStatuses(
            $out->sortBy(fn(array $r) => $r['effective_date']->timestamp . '_' . $r['time_slot'])->values(),
        );
    }

    public function recentProgressNotes(int $therapistId, int $limit = 15): Collection
    {
        return ProgressNote::query()
            ->where('therapist_id', $therapistId)
            ->with(['child', 'service'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|bool>}
     */
    public function assignedChildrenApiResponse(int $therapistId, int $perPage, int $page): array
    {
        $paginator = $this->paginateAssignedChildren($therapistId, $perPage, $page, '');

        return [
            'data' => $paginator->getCollection()
                ->map(fn(array $row): array => $this->serializeAssignedChildRowForApi($row))
                ->values()
                ->all(),
            'meta' => $this->apiPaginationMeta($paginator),
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|bool>, default_range: ?array<string, string>}
     */
    public function therapistScheduleApiResponse(
        int $therapistId,
        ?string $startDate,
        ?string $endDate,
        ?string $statusFilter,
        ?int $childId,
        int $perPage,
        int $page,
        string $path,
    ): array {
        $paginator = $this->paginateTherapistSessionsFiltered(
            $therapistId,
            $startDate,
            $endDate,
            $statusFilter,
            $childId,
            $perPage,
            $page,
            $path,
        );

        $hasDateFilter = $startDate !== null || $endDate !== null;

        return [
            'data' => collect($paginator->items())
                ->map(fn(array $row): array => $this->serializeTherapistSessionRowForApi($row))
                ->values()
                ->all(),
            'meta' => $this->apiPaginationMeta($paginator),
            'default_range' => $hasDateFilter ? null : [
                'from' => now()->subWeeks(self::SESSIONS_DEFAULT_PAST_WEEKS)->toDateString(),
                'to'   => now()->addWeeks(self::SESSIONS_DEFAULT_FUTURE_WEEKS)->toDateString(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function serializeAssignedChildRowForApi(array $row): array
    {
        $child = $row['child'];
        $next = $row['next_session'];
        $nextDate = $next?->session_date;

        return [
            'id'                    => $child->id,
            'full_name'             => $child->full_name,
            'age'                   => $child->age,
            'gender'                => $child->gender,
            'disabilities'          => $child->disabilities->map(fn($d) => [
                'id'   => $d->id,
                'name' => $d->name,
            ])->values()->all(),
            'branch_name'           => $row['branch_name'],
            'last_assessment_date'  => $row['last_assessment']?->date?->format('Y-m-d'),
            'last_session_date'     => $row['last_session_date'],
            'next_session'          => $next ? [
                'enrollment_schedule_id' => $next->id,
                'session_date'           => $nextDate instanceof Carbon
                    ? $nextDate->format('Y-m-d')
                    : (is_string($nextDate) ? $nextDate : null),
                'day'                    => $next->day,
                'time_slot'              => $next->time_slot,
            ] : null,
            'enrollment_status'     => $row['enrollment_status'],
            'services_label'        => $row['services_label'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function serializeTherapistSessionRowForApi(array $row): array
    {
        $sch = $row['schedule'];
        $occ = $row['effective_date'];

        return [
            'enrollment_schedule_id'      => $sch->id,
            'enrollment_id'               => $sch->enrollment_id,
            'session_date'                => $occ->toDateString(),
            'status'                      => (string) ($row['status'] ?? $sch->status),
            'time_slot'                   => (string) ($row['time_slot'] ?? $sch->time_slot),
            'child_id'                    => $sch->enrollment?->child_id,
            'child_name'                  => $row['child_name'] ?? ($sch->enrollment?->child?->full_name ?? '—'),
            'service_name'                => $row['service_name'] ?? ($sch->enrollment?->service?->name ?? '—'),
            'branch_name'                 => $row['branch_name'] ?? ($sch->branch?->name ?? $sch->enrollment?->branch?->name ?? '—'),
            'progress_meta'               => $row['progress_meta'] ?? null,
            'can_start_session_now'       => (bool) ($row['can_start_session_now'] ?? false),
            'session_start_window_passed' => (bool) ($row['session_start_window_passed'] ?? false),
            'occurrence_starts_at'        => isset($row['occurrence_starts_at'])
                ? $row['occurrence_starts_at']->toIso8601String()
                : null,
            'occurrence_ends_at'          => isset($row['occurrence_ends_at'])
                ? $row['occurrence_ends_at']->toIso8601String()
                : null,
        ];
    }

    /**
     * @return array{current_page: int, last_page: int, per_page: int, total: int, has_more: bool}
     */
    private function apiPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'per_page'       => $paginator->perPage(),
            'total'          => $paginator->total(),
            'has_more'       => $paginator->hasMorePages(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDashboardPage(User $therapist): array
    {
        $tid = (int) $therapist->id;
        $todayAssessments = $this->assessmentService->getTherapistTodayAssessments($tid);
        $todaySessions = $this->schedulesForCalendarDay($tid, now());
        $assessmentCounts = $this->assessmentService->getTherapistDashboardAssessmentCounts($tid);
        $previewLimit = self::DASHBOARD_PREVIEW_LIMIT;

        return [
            'stats' => $this->dashboardStats($tid, [
                'today_assessments_count' => $todayAssessments->count(),
                'today_sessions_count'    => $todaySessions->count(),
                'assessment_counts'       => $assessmentCounts,
            ]),
            'today_assessments_preview' => $todayAssessments->take($previewLimit),
            'today_assessments_total'   => $todayAssessments->count(),
            'today_sessions_preview'    => $todaySessions->take($previewLimit),
            'today_sessions_total'      => $todaySessions->count(),
        ];
    }
}
