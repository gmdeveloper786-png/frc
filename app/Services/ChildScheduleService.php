<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\Payment;
use Carbon\Carbon;
use App\Services\SessionTimeSlotParser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

/**
 * Expands recurring enrolments into dated occurrences and paginates them for:
 * - Child portal (`child.schedule.*`)
 * - Staff enrolment full schedule (`enrollments.schedule*`), authorised per {@see \App\Policies\EnrollmentPolicy::viewFullSchedule}.
 *
 * Staff-facing child CRUD stays in {@see \App\Http\Controllers\Web\ChildController}.
 */
class ChildScheduleService
{
    private const PER_PAGE = 15;

    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $enrollmentOccurrencesCache = [];

    public function __construct(
        private readonly SessionOccurrenceStateService $occurrenceState,
    ) {}

    public function getPaginatedSchedules(int $childId, Request $request): LengthAwarePaginator
    {
        $filtered = $this->applyFilters($this->getExpandedOccurrences($childId), $request);
        $sorted = $this->sortForDisplay($filtered);

        $page = max(1, (int) $request->query('page', 1));
        $total = $sorted->count();
        $items = $sorted->forPage($page, self::PER_PAGE)->values();

        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        return $paginator->withQueryString();
    }

    /**
     * Next upcoming session (scheduled, today or future), ignoring list filters — summary card only.
     *
     * @return array<string, mixed>|null
     */
    public function getNextUpcomingOccurrence(int $childId): ?array
    {
        $today = now()->startOfDay();

        $next = $this->getExpandedOccurrences($childId)
            ->filter(fn(array $row): bool => $row['status'] === 'scheduled'
                && $row['session_date']->greaterThanOrEqualTo($today))
            ->sortBy(fn(array $row): int => $this->sessionStartTimestamp($row))
            ->first();

        return $next ?: null;
    }

    /**
     * @return array{therapists: Collection<int, array{id:int,name:string}>, services: Collection<int, array{id:int,name:string}>}
     */
    public function getFilterOptions(int $childId): array
    {
        $rows = $this->getExpandedOccurrences($childId);

        $therapists = $rows
            ->filter(fn(array $r) => ! empty($r['therapist_id']))
            ->unique('therapist_id')
            ->map(fn(array $r) => ['id' => (int) $r['therapist_id'], 'name' => $r['therapist_name']])
            ->values();

        $services = $rows
            ->filter(fn(array $r) => ! empty($r['service_id']))
            ->unique('service_id')
            ->map(fn(array $r) => ['id' => (int) $r['service_id'], 'name' => $r['service_name']])
            ->values();

        return compact('therapists', 'services');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOccurrenceDetail(int $childId, int $scheduleId, string $sessionDate): ?array
    {
        $schedule = EnrollmentSchedule::with(['enrollment', 'therapist', 'branch'])
            ->find($scheduleId);

        if (! $schedule || ! $schedule->enrollment || (int) $schedule->enrollment->child_id !== $childId) {
            return null;
        }

        if (! $schedule->enrollment->isVisibleToChild()) {
            return null;
        }

        try {
            $parsed = Carbon::parse($sessionDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $this->getExpandedOccurrences($childId)->first(function (array $row) use ($scheduleId, $parsed): bool {
            return (int) $row['schedule_id'] === $scheduleId && $row['session_date']->equalTo($parsed);
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getExpandedOccurrences(int $childId): Collection
    {
        $enrollments = Enrollment::query()
            ->where('child_id', $childId)
            ->visibleToChild()
            ->latest()
            ->get();

        $out = collect();

        foreach ($enrollments as $enrollment) {
            $out = $out->concat($this->getExpandedOccurrencesForEnrollmentId((int) $enrollment->id));
        }

        return $out;
    }

    /**
     * Expanded occurrences for a single enrolment (any status — staff/student routes enforce visibility).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getExpandedOccurrencesForEnrollmentId(int $enrollmentId): Collection
    {
        if (array_key_exists($enrollmentId, $this->enrollmentOccurrencesCache)) {
            return $this->enrollmentOccurrencesCache[$enrollmentId];
        }

        $enrollment = Enrollment::with(['schedules.therapist', 'schedules.branch', 'branch', 'service', 'therapist'])
            ->find($enrollmentId);

        if (! $enrollment) {
            return $this->enrollmentOccurrencesCache[$enrollmentId] = collect();
        }

        return $this->enrollmentOccurrencesCache[$enrollmentId] = $this->applyOccurrenceStatuses(
            $enrollment,
            $this->expandEnrollmentSchedules($enrollment),
        );
    }

    /**
     * Staff enrolment full schedule: one expansion pass for list + stats (filters use schedule templates).
     *
     * @return array{
     *   paginator: LengthAwarePaginator,
     *   filterOptions: array{therapists: Collection, services: Collection},
     *   stats: array{total_sessions:int, completed_sessions:int, upcoming_sessions:int, payment_status:?string, payment_status_label:string}
     * }
     */
    public function getEnrollmentFullSchedulePageData(Enrollment $enrollment, Request $request): array
    {
        $enrollment->loadMissing(['schedules.therapist', 'service', 'branch', 'therapist']);
        $rows = $this->getExpandedOccurrencesForEnrollmentId((int) $enrollment->id);

        return [
            'paginator'     => $this->paginateExpandedOccurrenceRows($rows, $request),
            'filterOptions' => $this->filterOptionsFromEnrollment($enrollment),
            'stats'         => $this->statsFromExpandedRows($enrollment, $rows),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function applyOccurrenceStatuses(Enrollment $enrollment, Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $schedulesById = $enrollment->schedules->keyBy('id');
        $recurringIds = $enrollment->schedules
            ->filter(fn (EnrollmentSchedule $s): bool => $this->occurrenceState->isRecurringTemplate($s))
            ->pluck('id');

        $occurrencesBySchedule = $recurringIds->isEmpty()
            ? collect()
            : \App\Models\EnrollmentScheduleOccurrence::query()
                ->whereIn('enrollment_schedule_id', $recurringIds->all())
                ->get()
                ->groupBy('enrollment_schedule_id');

        return $rows->map(function (array $row) use ($schedulesById, $occurrencesBySchedule): array {
            $schedule = $schedulesById->get((int) ($row['schedule_id'] ?? 0));
            if ($schedule === null) {
                return $row;
            }

            $sessionDate = $row['session_date'] instanceof Carbon
                ? $row['session_date']->copy()->startOfDay()
                : Carbon::parse((string) $row['session_date'])->startOfDay();

            if ($this->occurrenceState->isRecurringTemplate($schedule)) {
                $occurrence = $occurrencesBySchedule
                    ->get($schedule->id, collect())
                    ->first(fn (\App\Models\EnrollmentScheduleOccurrence $o): bool => $o->occurrence_date->isSameDay($sessionDate));
                $status = $this->occurrenceState->resolveRecurringOccurrenceStatus($schedule, $sessionDate, $occurrence);
            } else {
                $status = $this->occurrenceState->effectiveStatus($schedule, $sessionDate);
            }

            $row['status'] = $status;
            $row['status_label'] = $this->statusLabel($status);
            $row['badge_class'] = $this->statusBadgeClass($status);
            $row['sort_rank'] = $this->sortRank($sessionDate, $status);

            return $row;
        });
    }

    public function getPaginatedSchedulesForEnrollment(int $enrollmentId, Request $request): LengthAwarePaginator
    {
        return $this->paginateExpandedOccurrenceRows(
            $this->getExpandedOccurrencesForEnrollmentId($enrollmentId),
            $request,
        );
    }

    /**
     * @return array{therapists: Collection<int, array{id:int,name:string}>, services: Collection<int, array{id:int,name:string}>}
     */
    public function getFilterOptionsForEnrollment(int $enrollmentId): array
    {
        $enrollment = Enrollment::with(['schedules.therapist', 'service'])->find($enrollmentId);
        if (! $enrollment) {
            return ['therapists' => collect(), 'services' => collect()];
        }

        return $this->filterOptionsFromEnrollment($enrollment);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function paginateExpandedOccurrenceRows(Collection $rows, Request $request): LengthAwarePaginator
    {
        $filtered = $this->applyFilters($rows, $request);
        $sorted = $this->sortForDisplay($filtered);

        $page = max(1, (int) $request->query('page', 1));
        $total = $sorted->count();
        $items = $sorted->forPage($page, self::PER_PAGE)->values();

        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $paginator->withQueryString();
    }

    /**
     * @return array{therapists: Collection<int, array{id:int,name:string}>, services: Collection<int, array{id:int,name:string}>}
     */
    private function filterOptionsFromEnrollment(Enrollment $enrollment): array
    {
        $therapists = $enrollment->schedules
            ->filter(fn (EnrollmentSchedule $schedule): bool => (int) ($schedule->therapist_id ?? 0) > 0)
            ->unique('therapist_id')
            ->map(fn (EnrollmentSchedule $schedule) => [
                'id'   => (int) $schedule->therapist_id,
                'name' => $schedule->therapist?->full_name ?? '—',
            ])
            ->values();

        $services = collect();
        if ($enrollment->service_id) {
            $enrollment->loadMissing('service');
            $services = collect([[
                'id'   => (int) $enrollment->service_id,
                'name' => $enrollment->service?->name ?? '—',
            ]]);
        }

        return compact('therapists', 'services');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{total_sessions:int, completed_sessions:int, upcoming_sessions:int, payment_status:?string, payment_status_label:string}
     */
    private function statsFromExpandedRows(Enrollment $enrollment, Collection $rows): array
    {
        $today = now()->startOfDay();

        $completedSessions = $rows->where('status', 'completed')->count();
        $upcomingSessions = $rows->filter(
            fn (array $r): bool => $r['status'] === 'scheduled'
                && $r['session_date']->greaterThanOrEqualTo($today),
        )->count();

        return [
            'total_sessions'       => (int) $enrollment->total_sessions,
            'completed_sessions'   => $completedSessions,
            'upcoming_sessions'    => $upcomingSessions,
            'payment_status'       => $enrollment->payment_status,
            'payment_status_label' => Payment::labelForEnrollmentPaymentStatus($enrollment->payment_status),
        ];
    }

    /**
     * Occurrence row for staff/student enrolment-scoped detail (same shape as child portal rows).
     *
     * @return array<string, mixed>|null
     */
    public function getOccurrenceDetailForEnrollment(Enrollment $enrollment, int $scheduleId, string $sessionDate): ?array
    {
        try {
            $parsed = Carbon::parse($sessionDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $this->getExpandedOccurrencesForEnrollmentId($enrollment->id)->first(function (array $row) use ($scheduleId, $parsed): bool {
            return (int) $row['schedule_id'] === $scheduleId && $row['session_date']->equalTo($parsed);
        });
    }

    /**
     * @return array{total_sessions:int, completed_sessions:int, upcoming_sessions:int, payment_status:?string, payment_status_label:string}
     */
    public function getEnrollmentScheduleStats(Enrollment $enrollment): array
    {
        return $this->statsFromExpandedRows(
            $enrollment,
            $this->getExpandedOccurrencesForEnrollmentId((int) $enrollment->id),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function expandEnrollmentSchedules(Enrollment $enrollment): Collection
    {
        if ($enrollment->schedules->isEmpty()) {
            return collect();
        }

        if ($this->shouldGenerateRecurringGrid($enrollment)) {
            return collect($this->generateRecurringOccurrences($enrollment));
        }

        return $enrollment->schedules->map(fn(EnrollmentSchedule $s) => $this->mapDbRowToOccurrence($enrollment, $s));
    }

    private function shouldGenerateRecurringGrid(Enrollment $enrollment): bool
    {
        if (! $enrollment->repeat_weekly || $enrollment->schedules->isEmpty()) {
            return false;
        }

        return $enrollment->schedules->every(fn(EnrollmentSchedule $s) => $s->session_date === null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function generateRecurringOccurrences(Enrollment $enrollment): array
    {
        $templates = $enrollment->schedules->sortBy(function (EnrollmentSchedule $s) {
            return [$this->dayOffsetFromMonday((string) $s->day), (string) $s->time_slot];
        })->values();

        $anchor = $this->enrollmentAnchorDate($enrollment);
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $total = max(0, (int) $enrollment->total_sessions);
        $emitted = 0;
        $out = [];
        $guard = 0;

        while ($emitted < $total && $guard < 520) {
            foreach ($templates as $tpl) {
                $sessionDay = $weekStart->copy()->addDays($this->dayOffsetFromMonday((string) $tpl->day))->startOfDay();
                if ($sessionDay->lt($anchor)) {
                    continue;
                }
                if ($emitted >= $total) {
                    break 2;
                }
                $out[] = $this->buildOccurrenceArray($enrollment, $tpl, $sessionDay);
                $emitted++;
            }
            $weekStart->addWeek();
            $guard++;
        }

        return $out;
    }

    private function mapDbRowToOccurrence(Enrollment $enrollment, EnrollmentSchedule $s): array
    {
        $date = $s->session_date
            ? Carbon::parse($s->session_date)->startOfDay()
            : $this->firstCalendarDateOnOrAfterAnchor((string) $s->day, $this->enrollmentAnchorDate($enrollment));

        return $this->buildOccurrenceArray($enrollment, $s, $date);
    }

    private function buildOccurrenceArray(Enrollment $enrollment, EnrollmentSchedule $schedule, Carbon $sessionDate): array
    {
        $therapistName = $schedule->therapist?->full_name ?? $enrollment->therapist?->full_name ?? '—';
        $branchName = $schedule->branch?->name ?? $enrollment->branch?->name ?? '—';
        $serviceName = $enrollment->service?->name ?? '—';

        return [
            'schedule_id'      => $schedule->id,
            'enrollment_id'    => $enrollment->id,
            'session_date'     => $sessionDate->copy(),
            'date_iso'         => $sessionDate->toDateString(),
            'day_label'        => $sessionDate->format('l'),
            'time_slot'        => (string) $schedule->time_slot,
            'branch_name'      => $branchName,
            'therapist_name'   => $therapistName,
            'therapist_id'     => $schedule->therapist_id ?? $enrollment->therapist_id,
            'service_name'     => $serviceName,
            'service_id'       => $enrollment->service_id,
            'status'           => (string) $schedule->status,
            'status_label'     => $this->statusLabel((string) $schedule->status),
            'badge_class'      => $this->statusBadgeClass((string) $schedule->status),
            'enrollment_label' => 'Enrollment #' . $enrollment->id,
            'notes'            => $schedule->session_notes,
            'sort_rank'        => $this->sortRank($sessionDate, (string) $schedule->status),
        ];
    }

    private function enrollmentAnchorDate(Enrollment $enrollment): Carbon
    {
        if ($enrollment->schedule_start_date) {
            return Carbon::parse($enrollment->schedule_start_date)->startOfDay();
        }

        $dt = $enrollment->approved_at ?? $enrollment->created_at;

        return Carbon::parse($dt)->startOfDay();
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function applyFilters(Collection $rows, Request $request): Collection
    {
        $status = $request->query('status');
        if ($status === 'no_show') {
            $status = 'all';
        }
        $from = $request->query('date_from');
        $to = $request->query('date_to');
        $serviceId = $request->query('service_id');
        $therapistId = $request->query('therapist_id');

        return $rows->filter(function (array $row) use ($status, $from, $to, $serviceId, $therapistId): bool {
            if ($status && $status !== 'all' && $row['status'] !== $status) {
                return false;
            }
            if ($from) {
                try {
                    if ($row['session_date']->lt(Carbon::parse($from)->startOfDay())) {
                        return false;
                    }
                } catch (\Throwable) {
                    return false;
                }
            }
            if ($to) {
                try {
                    if ($row['session_date']->gt(Carbon::parse($to)->endOfDay())) {
                        return false;
                    }
                } catch (\Throwable) {
                    return false;
                }
            }
            if ($serviceId && (int) $row['service_id'] !== (int) $serviceId) {
                return false;
            }
            if ($therapistId && (int) ($row['therapist_id'] ?? 0) !== (int) $therapistId) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortForDisplay(Collection $rows): Collection
    {
        return $rows->sort(function (array $a, array $b): int {
            foreach ([0, 1, 2] as $idx) {
                if (($a['sort_rank'][$idx] ?? 0) !== ($b['sort_rank'][$idx] ?? 0)) {
                    return ($a['sort_rank'][$idx] ?? 0) <=> ($b['sort_rank'][$idx] ?? 0);
                }
            }

            return $this->sessionStartTimestamp($a) <=> $this->sessionStartTimestamp($b);
        })->values();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sessionStartTimestamp(array $row): int
    {
        $sessionDate = $row['session_date'] ?? null;
        if (! $sessionDate instanceof Carbon) {
            return 0;
        }

        $slot = (string) ($row['time_slot'] ?? '');
        if ($slot === '') {
            return $sessionDate->timestamp;
        }

        return SessionTimeSlotParser::occurrenceStart($sessionDate, $slot)->timestamp;
    }

    /**
     * Sort key: (1) upcoming scheduled — nearest date first; (2) everything else — ascending session date.
     *
     * @return array{0:int,1:int,2:int}
     */
    private function sortRank(Carbon $sessionDate, string $status): array
    {
        $today = now()->startOfDay();
        $isScheduled = $status === 'scheduled';
        $isFutureOrToday = $sessionDate->greaterThanOrEqualTo($today);

        if ($isScheduled && $isFutureOrToday) {
            return [0, $sessionDate->timestamp, 0];
        }

        return [1, $sessionDate->timestamp, 0];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'no_show' => 'No Show',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'scheduled' => 'badge-session-scheduled',
            'in_progress' => 'badge-session-in-progress',
            'completed' => 'badge-session-completed',
            'cancelled' => 'badge-session-cancelled',
            'no_show' => 'badge-session-no-show',
            default => 'badge-draft',
        };
    }

    private function dayNameToIsoDow(string $day): ?int
    {
        $d = strtolower(trim($day));
        if ($d === '') {
            return null;
        }

        return match (true) {
            str_starts_with($d, 'mon') => 1,
            str_starts_with($d, 'tue') => 2,
            str_starts_with($d, 'wed') => 3,
            str_starts_with($d, 'thu') => 4,
            str_starts_with($d, 'fri') => 5,
            str_starts_with($d, 'sat') => 6,
            str_starts_with($d, 'sun') => 7,
            default => null,
        };
    }

    private function dayOffsetFromMonday(string $day): int
    {
        return match ($this->dayNameToIsoDow($day)) {
            1 => 0,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            6 => 5,
            7 => 6,
            default => 0,
        };
    }

    private function firstCalendarDateOnOrAfterAnchor(string $day, Carbon $anchor): Carbon
    {
        $iso = $this->dayNameToIsoDow($day);
        if ($iso === null) {
            return $anchor->copy();
        }

        for ($i = 0; $i < 21; $i++) {
            $c = $anchor->copy()->addDays($i)->startOfDay();
            if ((int) $c->dayOfWeekIso === $iso) {
                return $c;
            }
        }

        return $anchor->copy();
    }
}
