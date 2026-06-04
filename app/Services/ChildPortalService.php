<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only portal data for authenticated child accounts (dashboard + supporting `/my-*` pages).
 *
 * For staff managing children (list/approve/edit), use {@see \App\Http\Controllers\Web\ChildController}.
 */
class ChildPortalService
{
    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly ChildScheduleService $childSchedule,
        private readonly UserNotificationService $userNotifications,
    ) {}

    /**
     * @return array{
     *   account_status:string,
     *   enrollment:?Enrollment,
     *   payments:Collection<int,Payment>,
     *   assessments:Collection,
     *   upcoming_assessment:?Assessment,
     *   next_session_display:?array,
     *   pending_payment_verification_count:int,
     *   session_counts:array{total:int,completed:int,upcoming_scheduled:int},
     *   total_sessions_completed:int,
     *   dashboard_summary:array{
     *     total_enrollments:int,
     *     total_assessments:int,
     *     slips_pending:int,
     *     total_expected:float,
     *     total_paid:float,
     *     pending_overdue:float,
     *     pending_verification:float
     *   },
     *   notifications:Collection,
     *   unread_count:int
     * }
     */
    public function getDashboardPayload(User $child): array
    {
        $childId = (int) $child->id;
        $assessmentQuery = $this->childVisibleAssessmentsQuery($childId);

        $recentAssessments = (clone $assessmentQuery)
            ->with(['branch'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->limit(5)
            ->get();

        $assessmentCount = (clone $assessmentQuery)->count();

        $recentPayments = Payment::query()
            ->where('child_id', $childId)
            ->latest()
            ->limit(5)
            ->get();

        $primaryEnrollment = Enrollment::query()
            ->with(['branch', 'service', 'therapist'])
            ->where('child_id', $childId)
            ->visibleToChild()
            ->latest()
            ->first();

        $slipEnrollments = $this->getEnrollmentsForFeeSlipUpload($child);

        return [
            'account_status'                     => $child->status,
            'enrollment'                         => $primaryEnrollment,
            'payments'                           => $recentPayments,
            'assessments'                        => $recentAssessments,
            'upcoming_assessment'                => $this->firstUpcomingAssessment($childId),
            'next_session_display'               => $this->buildNextSessionDisplayForChild($childId),
            'pending_payment_verification_count' => Payment::query()
                ->where('child_id', $childId)
                ->where('status', 'pending_verification')
                ->count(),
            'session_counts'                     => $this->sessionCountsForChild($childId),
            'total_sessions_completed'           => $this->countCompletedSessionsForChild($childId),
            'dashboard_summary'                  => $this->buildDashboardSummary($childId, $assessmentCount),
            'can_upload_fee_slip'                => $slipEnrollments->isNotEmpty(),
            'notifications'                      => $this->userNotifications->getLatestNotifications($child, 8),
            'unread_count'                       => $this->userNotifications->getUnreadCount($child),
        ];
    }

    public function getEnrollmentForPortal(int $childId): Collection
    {
        return Enrollment::with([
            'branch',
            'service',
            'therapist',
            'paidPayments',
            'schedules' => fn ($q) => $q->with('therapist')->orderBy('session_date')->orderBy('time_slot'),
        ])
            ->where('child_id', $childId)
            ->visibleToChild()
            ->latest()
            ->get();
    }

    /**
     * Enrollment rows with computed portal metrics (My Enrollment page).
     *
     * @return Collection<int, array{
     *   enrollment: Enrollment,
     *   completed_sessions: int,
     *   remaining_sessions: int,
     *   upcoming_sessions: int,
     *   days_per_week: int,
     *   next_session: ?array,
     *   recurring_summary: ?array,
     *   show_fee_fully_paid_notice: bool,
     *   show_upload_slip_button: bool
     * }>
     */
    public function presentEnrollmentsForPortal(int $childId): Collection
    {
        return $this->getEnrollmentForPortal($childId)->map(fn (Enrollment $e) => $this->presentEnrollmentRow($e));
    }

    /**
     * Same shape as {@see presentEnrollmentsForPortal} rows, for the child enrollment detail page.
     * Loads relations needed for the full breakdown. Caller must verify ownership + {@see Enrollment::isVisibleToChild()}.
     *
     * @return array{
     *   enrollment: Enrollment,
     *   completed_sessions: int,
     *   remaining_sessions: int,
     *   upcoming_sessions: int,
     *   days_per_week: int,
     *   next_session: ?array,
     *   recurring_summary: ?array,
     *   show_fee_fully_paid_notice: bool,
     *   show_upload_slip_button: bool
     * }
     */
    public function presentEnrollmentDetailForPortal(Enrollment $enrollment): array
    {
        $enrollment->load([
            'branch',
            'service',
            'therapist',
            'schedules' => fn ($q) => $q->with('therapist')->orderBy('session_date')->orderBy('time_slot'),
        ]);

        return $this->presentEnrollmentRow($enrollment);
    }

    /**
     * @deprecated Kept only if callers need a single “primary” row; prefer {@see getEnrollmentsForFeeSlipUpload} for fee slips.
     */
    public function getVisibleEnrollmentSummary(User $child): ?Enrollment
    {
        return Enrollment::with(['branch', 'service', 'therapist'])
            ->where('child_id', $child->id)
            ->visibleToChild()
            ->latest()
            ->first();
    }

    /**
     * All programmes that still have a balance or a slip awaiting verification (for the picker).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Enrollment>
     */
    public function getEnrollmentsForSlipPicker(User $child): Collection
    {
        return Enrollment::with(['branch', 'service', 'therapist'])
            ->where('child_id', $child->id)
            ->visibleToChild()
            ->whereIn('status', ['approved', 'active'])
            ->orderByDesc('remaining_amount')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Enrollment $e): bool => $e->outstandingAmount() > 0
                || $e->sumPendingVerificationAmount() > 0)
            ->values();
    }

    /**
     * Enrollments where the child may upload a bank / mobile-wallet fee slip (each programme billed separately).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Enrollment>
     */
    public function getEnrollmentsForFeeSlipUpload(User $child): Collection
    {
        return Enrollment::with(['branch', 'service', 'therapist'])
            ->where('child_id', $child->id)
            ->visibleToChild()
            ->whereIn('status', ['approved', 'active'])
            ->orderByDesc('remaining_amount')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Enrollment $e): bool => $e->outstandingForSlipUpload() > 0)
            ->values();
    }

    public function childHasVisibleEnrollment(User $child): bool
    {
        return Enrollment::query()
            ->where('child_id', $child->id)
            ->visibleToChild()
            ->exists();
    }

    public function canUploadFeeSlip(?Enrollment $enrollment): bool
    {
        return $enrollment instanceof Enrollment
            && $enrollment->isVisibleToChild()
            && in_array($enrollment->status, ['approved', 'active'], true)
            && $enrollment->outstandingForSlipUpload() > 0;
    }

    /**
     * Aggregated metrics across all child-visible enrollments (dashboard stat cards).
     *
     * @return array{
     *   total_enrollments:int,
     *   total_assessments:int,
     *   slips_pending:int,
     *   total_expected:float,
     *   total_paid:float,
     *   pending_overdue:float,
     *   pending_verification:float
     * }
     */
    private function buildDashboardSummary(int $childId, int $totalAssessments): array
    {
        $enrollmentRows = Enrollment::query()
            ->where('child_id', $childId)
            ->visibleToChild()
            ->whereIn('status', ['approved', 'active'])
            ->get(['id', 'final_total', 'paid_amount', 'remaining_amount']);

        $enrollmentTotals = Enrollment::query()
            ->selectRaw('COUNT(*) as enrollment_count, COALESCE(SUM(final_total), 0) as sum_expected, COALESCE(SUM(paid_amount), 0) as sum_paid')
            ->where('child_id', $childId)
            ->visibleToChild()
            ->first();

        $uploadableRemaining = $enrollmentRows->sum(
            fn (Enrollment $e): float => $e->outstandingForSlipUpload()
        );

        $pendingVerificationAmount = (float) Payment::query()
            ->where('child_id', $childId)
            ->where('status', 'pending_verification')
            ->sum('amount');

        return [
            'total_enrollments' => (int) ($enrollmentTotals->enrollment_count ?? 0),
            'total_assessments' => $totalAssessments,
            'slips_pending'     => Payment::query()
                ->where('child_id', $childId)
                ->where('status', 'pending_verification')
                ->count(),
            'total_expected'    => (float) ($enrollmentTotals->sum_expected ?? 0),
            'total_paid'        => (float) ($enrollmentTotals->sum_paid ?? 0),
            'pending_overdue'   => (float) $uploadableRemaining,
            'pending_verification' => $pendingVerificationAmount,
        ];
    }

    private function childVisibleAssessmentsQuery(int $childId): Builder
    {
        return Assessment::query()
            ->where('status', '!=', 'draft')
            ->where(function ($q): void {
                $q->where('status', '!=', 'cancelled')
                    ->orWhere(function ($q2): void {
                        $q2->where('status', 'cancelled')
                            ->where(function ($q3): void {
                                $q3->whereNull('cancelled_previous_status')
                                    ->orWhere('cancelled_previous_status', 'publish');
                            });
                    });
            })
            ->whereHas('children', fn ($q) => $q->where('users.id', $childId));
    }

    private function firstUpcomingAssessment(int $childId): ?Assessment
    {
        return Assessment::query()
            ->with(['branch', 'therapist', 'services'])
            ->where('status', 'publish')
            ->whereHas('children', fn ($q) => $q->where('users.id', $childId))
            ->where(function ($q): void {
                $q->whereDate('date', '>', now()->toDateString())
                    ->orWhereDate('date', now()->toDateString());
            })
            ->orderBy('date')
            ->orderBy('time')
            ->first();
    }

    /**
     * @return ?array{
     *   date_label:string,
     *   day_label:string,
     *   time_slot:string,
     *   therapist:string,
     *   service:string,
     *   status:string,
     *   badge:string,
     *   schedule_id:int,
     *   date_iso:string
     * }
     */
    private function buildNextSessionDisplayForChild(int $childId): ?array
    {
        $occ = $this->childSchedule->getNextUpcomingOccurrence($childId);
        if ($occ === null) {
            return null;
        }

        /** @var Carbon $sessionDate */
        $sessionDate = $occ['session_date'];

        return [
            'date_label'  => $sessionDate->format('d M Y'),
            'day_label'   => (string) ($occ['day_label'] ?? $sessionDate->format('l')),
            'time_slot'   => (string) ($occ['time_slot'] ?? ''),
            'therapist'   => (string) ($occ['therapist_name'] ?? '—'),
            'service'     => (string) ($occ['service_name'] ?? '—'),
            'status'      => (string) ($occ['status'] ?? 'scheduled'),
            'badge'       => (string) ($occ['badge_class'] ?? $this->scheduleStatusBadgeClass((string) ($occ['status'] ?? 'scheduled'))),
            'schedule_id' => (int) ($occ['schedule_id'] ?? 0),
            'date_iso'    => (string) ($occ['date_iso'] ?? $sessionDate->toDateString()),
        ];
    }

    /**
     * Completed session occurrences across all visible enrollments (recurring + fixed-date).
     */
    public function countCompletedSessionsForChild(int $childId): int
    {
        return (int) $this->childSchedule->getExpandedOccurrences($childId)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * @return array{total:int,completed:int,upcoming_scheduled:int}
     */
    private function sessionCountsForChild(int $childId): array
    {
        $rows = $this->childSchedule->getExpandedOccurrences($childId);
        $today = now()->startOfDay();

        return [
            'total'              => $rows->count(),
            'completed'          => $rows->where('status', 'completed')->count(),
            'upcoming_scheduled' => $rows->filter(
                fn (array $r): bool => ($r['status'] ?? '') === 'scheduled'
                    && $r['session_date']->greaterThanOrEqualTo($today),
            )->count(),
        ];
    }

    /**
     * @return array{total:int,completed:int,upcoming_scheduled:int}
     */
    private function sessionCountsForEnrollment(?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return ['total' => 0, 'completed' => 0, 'upcoming_scheduled' => 0];
        }

        $stats = $this->childSchedule->getEnrollmentScheduleStats($enrollment);

        return [
            'total'              => (int) $stats['total_sessions'],
            'completed'          => (int) $stats['completed_sessions'],
            'upcoming_scheduled' => (int) $stats['upcoming_sessions'],
        ];
    }

    public function countCompletedSessionsForEnrollment(Enrollment $enrollment): int
    {
        return (int) $this->childSchedule->getEnrollmentScheduleStats($enrollment)['completed_sessions'];
    }

    /**
     * Upcoming = schedules whose workflow status is still scheduled (or upcoming if introduced later),
     * and either no fixed session_date yet (recurring weekly template rows) or session_date is today/future.
     */
    public function countDistinctWeekdaysForEnrollment(Enrollment $enrollment): int
    {
        $seen = [];
        foreach ($enrollment->schedules->pluck('day') as $day) {
            $key = strtolower(trim((string) $day));
            if ($key !== '') {
                $seen[$key] = true;
            }
        }

        return count($seen);
    }

    public function countUpcomingSessionsForEnrollment(Enrollment $enrollment): int
    {
        return (int) $enrollment->schedules->filter(function ($schedule): bool {
            if (! $this->isScheduleUpcomingEligibleStatus($schedule->status)) {
                return false;
            }

            if ($schedule->session_date === null) {
                return true;
            }

            return $schedule->session_date->greaterThanOrEqualTo(now()->startOfDay());
        })->count();
    }

    /**
     * @return ?array{
     *   date_label:string,
     *   day_label:string,
     *   time_slot:string,
     *   therapist:string,
     *   service:string,
     *   status:string,
     *   badge:string,
     *   schedule_id:int,
     *   date_iso:string
     * }
     */
    public function buildNextSessionCard(?Enrollment $enrollment): ?array
    {
        if (! $enrollment instanceof Enrollment || $enrollment->schedules->isEmpty()) {
            return null;
        }

        $enrollment->loadMissing(['therapist', 'service']);

        $candidates = [];

        foreach ($enrollment->schedules as $s) {
            if (! $this->isScheduleUpcomingEligibleStatus($s->status)) {
                continue;
            }

            $therapistName = $s->therapist?->full_name ?? $enrollment->therapist?->full_name ?? '—';
            $serviceName   = $enrollment->service?->name ?? '—';

            if ($s->session_date !== null) {
                $sortTs = $this->dateTimeTimestamp($s->session_date->copy()->startOfDay(), (string) $s->time_slot);
                if ($sortTs < now()->timestamp) {
                    continue;
                }

                $candidates[] = [
                    'sort'         => $sortTs,
                    'date_label'   => $s->session_date->format('d M Y'),
                    'day_label'    => (string) $s->day,
                    'time_slot'    => (string) $s->time_slot,
                    'therapist'    => $therapistName,
                    'service'      => $serviceName,
                    'status'       => (string) $s->status,
                    'badge'        => $this->scheduleStatusBadgeClass($s->status),
                    'schedule_id'  => (int) $s->id,
                    'date_iso'     => $s->session_date->toDateString(),
                ];

                continue;
            }

            $sortTs = $this->nextTemplateOccurrenceTimestamp($enrollment, (string) $s->day, (string) $s->time_slot);
            if ($sortTs === null) {
                continue;
            }

            $occurrenceDate = Carbon::createFromTimestamp($sortTs)->timezone(config('app.timezone'));

            $candidates[] = [
                'sort'         => $sortTs,
                'date_label'   => $occurrenceDate->format('d M Y'),
                'day_label'    => (string) $s->day,
                'time_slot'    => (string) $s->time_slot,
                'therapist'    => $therapistName,
                'service'      => $serviceName,
                'status'       => (string) $s->status,
                'badge'        => $this->scheduleStatusBadgeClass($s->status),
                'schedule_id'  => (int) $s->id,
                'date_iso'     => $occurrenceDate->toDateString(),
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        $picked = $candidates[0];
        unset($picked['sort']);

        return $picked;
    }

    /**
     * @return ?array{
     *   selected_days:string,
     *   weekly_slots:int,
     *   duration_label:?string,
     *   total_sessions:int,
     *   explanation:string
     * }
     */
    public function buildRecurringWeeklySummary(Enrollment $enrollment): ?array
    {
        if (! $enrollment->repeat_weekly || $enrollment->schedules->isEmpty()) {
            return null;
        }

        $weeklySlots = $enrollment->schedules->count();
        $sortedDays  = $this->sortAndFormatWeekdays($enrollment->schedules->pluck('day'));
        $days        = $sortedDays === [] ? '—' : implode(', ', $sortedDays);

        $durationLabel = null;
        if ($enrollment->duration_value && $enrollment->duration_unit) {
            $durationLabel = $this->formatDurationLabel((int) $enrollment->duration_value, (string) $enrollment->duration_unit);
        }

        $total = (int) $enrollment->total_sessions;

        $startLabel = $enrollment->schedule_start_date?->format('d M Y');
        $explanation = "Total {$total} sessions are billed for this programme.";
        if ($startLabel) {
            $explanation .= " First session week begins on or after {$startLabel}.";
        }
        if ($weeklySlots > 0 && $durationLabel) {
            $explanation .= " You have {$weeklySlots} weekly slot".($weeklySlots === 1 ? '' : 's').' ('.($days ?: 'selected days').") spanning {$durationLabel}.";
        } elseif ($weeklySlots > 0) {
            $explanation .= ' Weekly slots: '.($days ?: ($weeklySlots.' slot(s)')).'.';
        }

        return [
            'selected_days'   => $days ?: '—',
            'weekly_slots'    => $weeklySlots,
            'duration_label'  => $durationLabel,
            'total_sessions'  => $total,
            'explanation'     => $explanation,
        ];
    }

    public function showEnrollmentFeeFullyPaidNotice(Enrollment $enrollment): bool
    {
        return $enrollment->effectivePaymentStatus() === 'fully_paid';
    }

    public function showEnrollmentUploadSlipButton(Enrollment $enrollment): bool
    {
        return $enrollment->isVisibleToChild()
            && in_array($enrollment->status, ['approved', 'active'], true)
            && $enrollment->outstandingForSlipUpload() > 0;
    }

    private function presentEnrollmentRow(Enrollment $enrollment): array
    {
        $scheduleStats = $this->childSchedule->getEnrollmentScheduleStats($enrollment);
        $completed = (int) $scheduleStats['completed_sessions'];
        $upcoming = (int) $scheduleStats['upcoming_sessions'];
        $total = (int) $scheduleStats['total_sessions'];

        return [
            'enrollment'                 => $enrollment,
            'completed_sessions'         => $completed,
            'remaining_sessions'         => max(0, $total - $completed),
            'upcoming_sessions'          => $upcoming,
            'days_per_week'              => $this->countDistinctWeekdaysForEnrollment($enrollment),
            'next_session'               => $this->buildNextSessionCard($enrollment),
            'recurring_summary'          => $this->buildRecurringWeeklySummary($enrollment),
            'show_fee_fully_paid_notice' => $this->showEnrollmentFeeFullyPaidNotice($enrollment),
            'show_upload_slip_button'    => $this->showEnrollmentUploadSlipButton($enrollment),
        ];
    }

    /**
     * Unique weekdays sorted Monday → Sunday; unknown tokens sort last.
     *
     * @return array<int, string>
     */
    private function sortAndFormatWeekdays(Collection $days): array
    {
        $seen = [];
        foreach ($days as $d) {
            $key = strtolower(trim((string) $d));
            if ($key === '') {
                continue;
            }
            if (! array_key_exists($key, $seen)) {
                $seen[$key] = (string) $d;
            }
        }

        return collect(array_values($seen))
            ->sortBy(fn (string $day) => $this->dayNameToIsoDow($day) ?? 99)
            ->values()
            ->map(fn (string $day) => $this->formatCanonicalWeekdayName($day))
            ->all();
    }

    private function formatCanonicalWeekdayName(string $day): string
    {
        $iso = $this->dayNameToIsoDow($day);

        return match ($iso) {
            1       => 'Monday',
            2       => 'Tuesday',
            3       => 'Wednesday',
            4       => 'Thursday',
            5       => 'Friday',
            6       => 'Saturday',
            7       => 'Sunday',
            default => Str::title(strtolower(trim($day))),
        };
    }

    private function isScheduleUpcomingEligibleStatus(?string $status): bool
    {
        return in_array($status, ['scheduled', 'upcoming'], true);
    }

    private function scheduleStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'scheduled', 'upcoming' => 'active',
            'completed'             => 'approved',
            default                 => $status,
        };
    }

    private function formatDurationLabel(int $value, string $unit): string
    {
        $label = match ($unit) {
            'weekly'  => $value === 1 ? '1 week' : "{$value} weeks",
            'monthly' => $value === 1 ? '1 month' : "{$value} months",
            'yearly'  => $value === 1 ? '1 year' : "{$value} years",
            default   => "{$value} {$unit}",
        };

        return $label;
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
            default                     => null,
        };
    }

    private function enrollmentScheduleAnchor(Enrollment $enrollment): Carbon
    {
        if ($enrollment->schedule_start_date) {
            return Carbon::parse($enrollment->schedule_start_date)->startOfDay();
        }

        return Carbon::parse($enrollment->approved_at ?? $enrollment->created_at)->startOfDay();
    }

    /**
     * First calendar date on or after the enrollment anchor that matches the weekday name.
     */
    private function firstSessionDateOnOrAfterAnchor(string $day, Carbon $anchor): ?Carbon
    {
        $iso = $this->dayNameToIsoDow($day);
        if ($iso === null) {
            return $anchor->copy();
        }

        for ($i = 0; $i < 380; $i++) {
            $c = $anchor->copy()->addDays($i)->startOfDay();
            if ((int) $c->dayOfWeekIso === $iso) {
                return $c;
            }
        }

        return null;
    }

    private function dateTimeTimestamp(Carbon $date, string $timeSlot): int
    {
        $parsed = $this->parseTimeSlotStart($timeSlot);

        return $date->copy()->setTime($parsed['hour'], $parsed['minute'], 0)->timestamp;
    }

    /**
     * @return array{hour:int,minute:int}
     */
    private function parseTimeSlotStart(string $timeSlot): array
    {
        $slot = trim($timeSlot);

        if (preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $slot, $m)) {
            $hour = (int) $m[1];
            $min  = (int) $m[2];
            $ap   = strtolower($m[3] ?? '');
            if ($ap === 'pm' && $hour < 12) {
                $hour += 12;
            }
            if ($ap === 'am' && $hour === 12) {
                $hour = 0;
            }

            return ['hour' => $hour, 'minute' => $min];
        }

        return ['hour' => 9, 'minute' => 0];
    }

    private function nextTemplateOccurrenceTimestamp(Enrollment $enrollment, string $day, string $timeSlot): ?int
    {
        $baseDate = $this->firstSessionDateOnOrAfterAnchor($day, $this->enrollmentScheduleAnchor($enrollment));
        if ($baseDate === null) {
            return null;
        }

        $ts = $this->dateTimeTimestamp($baseDate, $timeSlot);
        if ($ts >= now()->timestamp) {
            return $ts;
        }

        $shifted = $baseDate->copy()->addWeek();
        while ($this->dateTimeTimestamp($shifted, $timeSlot) < now()->timestamp) {
            $shifted->addWeek();
        }

        return $this->dateTimeTimestamp($shifted, $timeSlot);
    }
}
