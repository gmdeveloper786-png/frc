<?php

namespace App\Services;

use App\Models\EnrollmentSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Session occurrence metadata for therapist modals and staff/student schedule detail views.
 */
class SessionOccurrenceDetailService
{
    public function __construct(
        private readonly SessionOccurrenceStateService $occurrenceState,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function enrichTherapistSessionListRows(Collection $rows): Collection
    {
        return $rows->map(function (array $row): array {
            $sch = $row['schedule'];
            $occ = $row['effective_date'];

            $sessionDay = $occ->copy()->startOfDay();
            $timeSlot = (string) $sch->time_slot;
            $startsAt = SessionTimeSlotParser::occurrenceStart($sessionDay, $timeSlot);
            $endsAt = SessionTimeSlotParser::occurrenceEnd($sessionDay, $timeSlot);
            $row['occurrence_starts_at'] = $startsAt;
            $row['occurrence_ends_at'] = $endsAt;
            $occStatus = (string) ($row['status'] ?? $sch->status);
            $row['can_start_session_now'] = $occStatus === 'scheduled'
                && SessionTimeSlotParser::isSessionDayToday($sessionDay);
            $row['session_start_window_passed'] = $occStatus === 'scheduled'
                && SessionTimeSlotParser::isSessionDayPast($sessionDay);

            return $row;
        });
    }

    /**
     * JSON payload for therapist “View details” modal (includes sensitive cancellation reason).
     *
     * @return array<string, mixed>
     */
    public function buildTherapistOccurrenceDetail(EnrollmentSchedule $schedule, string $occurrenceDateIso): array
    {
        $schedule->loadMissing([
            'therapist',
            'branch',
            'enrollment.child',
            'enrollment.service',
            'startedBy:id,full_name',
            'completedBy:id,full_name',
            'cancelledBy:id,full_name',
        ]);

        try {
            $occurrence = Carbon::parse($occurrenceDateIso)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        $status = $this->occurrenceState->effectiveStatus($schedule, $occurrence);
        $occurrenceRow = $this->occurrenceState->findOccurrence($schedule, $occurrence);
        $occurrenceRow?->loadMissing(['startedBy:id,full_name', 'completedBy:id,full_name', 'cancelledBy:id,full_name']);

        $completionDisplay = $occurrenceRow?->completion_note ?? $schedule->completion_note;
        if (($completionDisplay === null || $completionDisplay === '') && $status === 'completed' && ($occurrenceRow?->session_notes ?? $schedule->session_notes)) {
            $completionDisplay = $occurrenceRow?->session_notes ?? $schedule->session_notes;
        }

        $cancellationDisplay = $occurrenceRow?->cancellation_reason ?? $schedule->cancellation_reason;
        if (($cancellationDisplay === null || $cancellationDisplay === '') && $status === 'cancelled' && ($occurrenceRow?->session_notes ?? $schedule->session_notes)) {
            $cancellationDisplay = $occurrenceRow?->session_notes ?? $schedule->session_notes;
        }

        return [
            'occurrence_date_iso'      => $occurrence->toDateString(),
            'session_date_label'     => $occurrence->format('l, d M Y'),
            'day_label'              => $occurrence->format('l'),
            'time_slot'              => (string) $schedule->time_slot,
            'child_name'             => $schedule->enrollment?->child?->full_name ?? '—',
            'therapist_name'         => $schedule->therapist?->full_name ?? '—',
            'service_name'           => $schedule->enrollment?->service?->name ?? '—',
            'branch_name'            => $schedule->branch?->name ?? $schedule->enrollment?->branch?->name ?? '—',
            'status'                 => $status,
            'status_label'           => $this->statusLabel($status),
            'child_id'               => $schedule->enrollment?->child_id,
            'enrollment_id'          => $schedule->enrollment_id,
            'service_id'             => $schedule->enrollment?->service_id,
            'enrollment_schedule_id' => $schedule->id,
            'started_at'             => ($occurrenceRow?->started_at ?? $schedule->started_at)?->toIso8601String(),
            'started_by_name'        => $occurrenceRow?->startedBy?->full_name ?? $schedule->startedBy?->full_name,
            'completed_at'           => ($occurrenceRow?->completed_at ?? $schedule->completed_at)?->toIso8601String(),
            'completed_by_name'      => $occurrenceRow?->completedBy?->full_name ?? $schedule->completedBy?->full_name,
            'completion_note'        => $completionDisplay,
            'cancelled_at'           => ($occurrenceRow?->cancelled_at ?? $schedule->cancelled_at)?->toIso8601String(),
            'cancelled_by_name'      => $occurrenceRow?->cancelledBy?->full_name ?? $schedule->cancelledBy?->full_name,
            'cancellation_reason'    => $cancellationDisplay,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'no_show' => 'No Show',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Authorize therapist actions on this schedule row.
     */
    public function authorizeAssignedTherapist(User $therapist, EnrollmentSchedule $schedule): void
    {
        abort_unless($therapist->isTherapist(), 403);
        abort_unless((int) $schedule->therapist_id === (int) $therapist->id, 403);
        $schedule->loadMissing('enrollment');
        abort_unless(
            $schedule->enrollment && in_array($schedule->enrollment->status, ['approved', 'active'], true),
            403,
        );
    }
}
