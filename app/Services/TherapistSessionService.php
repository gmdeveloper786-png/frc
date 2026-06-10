<?php

namespace App\Services;

use App\Models\EnrollmentSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TherapistSessionService
{
    public function __construct(
        private readonly TherapistPortalService $portal,
        private readonly NotificationService $notificationService,
        private readonly SessionOccurrenceStateService $occurrenceState,
        private readonly SessionFeedbackService $sessionFeedback,
    ) {}

    public function authorize(User $therapist, EnrollmentSchedule $schedule): void
    {
        abort_unless($therapist->isTherapist(), 403);
        abort_unless((int) $schedule->therapist_id === (int) $therapist->id, 403);
        $schedule->loadMissing('enrollment');
        abort_unless(
            $schedule->enrollment && in_array($schedule->enrollment->status, ['approved', 'active'], true),
            403,
        );
    }

    public function startSession(User $therapist, EnrollmentSchedule $schedule, string $sessionDateIso): EnrollmentSchedule
    {
        $this->authorize($therapist, $schedule);

        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();

        if (! $this->portal->therapistOwnsScheduleOccurrence((int) $therapist->id, $schedule, $sessionDay->toDateString())) {
            throw ValidationException::withMessages([
                'session_date' => ['This session date does not match this appointment.'],
            ]);
        }

        abort_unless(
            $this->occurrenceState->effectiveStatus($schedule, $sessionDay) === 'scheduled',
            403,
            'Only scheduled sessions can be started.',
        );

        if (! SessionTimeSlotParser::isSessionDayToday($sessionDay)) {
            if (SessionTimeSlotParser::isSessionDayPast($sessionDay)) {
                throw ValidationException::withMessages([
                    'session_date' => ['This session date has passed. You can no longer start it.'],
                ]);
            }
            throw ValidationException::withMessages([
                'session_date' => ['You can start this session only on its scheduled date.'],
            ]);
        }

        if ($this->occurrenceState->isRecurringTemplate($schedule)) {
            DB::transaction(function () use ($schedule, $therapist, $sessionDay): void {
                $this->occurrenceState->startOccurrence($therapist, $schedule, $sessionDay);
            });
        } else {
            DB::transaction(function () use ($schedule, $therapist): void {
                $schedule->update([
                    'status'     => 'in_progress',
                    'started_at' => now(),
                    'started_by' => $therapist->id,
                ]);
            });
        }

        $schedule->loadMissing('enrollment.child');
        if ($schedule->enrollment?->child) {
            $this->notificationService->notifySessionStarted($schedule, $schedule->enrollment->child);
        }

        return $schedule->fresh(['startedBy', 'completedBy', 'cancelledBy']);
    }

    /**
     * Start every child’s schedule in the same group slot for this calendar occurrence.
     *
     * @throws ValidationException if any member is not in scheduled state for this date
     */
    public function startGroupSessions(User $therapist, EnrollmentSchedule $anchor, string $sessionDateIso): int
    {
        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();
        $schedules  = $this->portal->groupSchedulesMatchingAnchorSlot($anchor);

        foreach ($schedules as $schedule) {
            if ($this->occurrenceState->effectiveStatus($schedule, $sessionDay) !== 'scheduled') {
                throw ValidationException::withMessages([
                    'session_date' => ['Every child in this group must be in “scheduled” status to start together.'],
                ]);
            }
        }

        $count = 0;
        foreach ($schedules as $schedule) {
            $this->startSession($therapist, $schedule, $sessionDateIso);
            $count++;
        }

        return $count;
    }

    /**
     * Complete all in-progress group members for this occurrence (shared completion note).
     */
    public function completeGroupSessions(
        User $therapist,
        EnrollmentSchedule $anchor,
        string $sessionDateIso,
        ?string $completionNote = null,
        array $ratings = [],
    ): int {
        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();
        $schedules  = $this->portal->groupSchedulesMatchingAnchorSlot($anchor);

        foreach ($schedules as $schedule) {
            if ($this->occurrenceState->effectiveStatus($schedule, $sessionDay) !== 'in_progress') {
                throw ValidationException::withMessages([
                    'session_date' => ['Every child in this group must be “in progress” to complete together.'],
                ]);
            }
        }

        $this->sessionFeedback->validateRatingsForSchedule($anchor, $ratings);

        $count = 0;
        foreach ($schedules as $schedule) {
            $this->completeSession($therapist, $schedule, $sessionDateIso, $completionNote, $ratings);
            $count++;
        }

        return $count;
    }

    /**
     * Cancel every scheduled or in-progress group member for this occurrence (shared reason).
     */
    public function cancelGroupSessions(User $therapist, EnrollmentSchedule $anchor, string $sessionDateIso, string $cancellationReason): int
    {
        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();
        $schedules  = $this->portal->groupSchedulesMatchingAnchorSlot($anchor);

        foreach ($schedules as $schedule) {
            $st = $this->occurrenceState->effectiveStatus($schedule, $sessionDay);
            if (! in_array($st, ['scheduled', 'in_progress'], true)) {
                throw ValidationException::withMessages([
                    'session_date' => ['One or more children in this group are not in a state that can be cancelled together.'],
                ]);
            }
        }

        $count = 0;
        foreach ($schedules as $schedule) {
            $this->cancelSession($therapist, $schedule, $sessionDateIso, $cancellationReason);
            $count++;
        }

        return $count;
    }

    public function completeSession(
        User $therapist,
        EnrollmentSchedule $schedule,
        string $sessionDateIso,
        ?string $completionNote = null,
        array $ratings = [],
    ): EnrollmentSchedule {
        $this->authorize($therapist, $schedule);

        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();

        if (! $this->portal->therapistOwnsScheduleOccurrence((int) $therapist->id, $schedule, $sessionDay->toDateString())) {
            throw ValidationException::withMessages([
                'session_date' => ['This session date does not match this appointment.'],
            ]);
        }

        abort_unless(
            $this->occurrenceState->effectiveStatus($schedule, $sessionDay) === 'in_progress',
            403,
            'Only in-progress sessions can be completed.',
        );

        $this->sessionFeedback->validateRatingsForSchedule($schedule, $ratings);

        if ($this->occurrenceState->isRecurringTemplate($schedule)) {
            DB::transaction(function () use ($schedule, $therapist, $sessionDay, $completionNote, $sessionDateIso, $ratings): void {
                $this->occurrenceState->completeOccurrence($therapist, $schedule, $sessionDay, $completionNote);
                $this->sessionFeedback->saveResponses($schedule, $sessionDateIso, $therapist, $ratings);
            });
        } else {
            DB::transaction(function () use ($schedule, $therapist, $completionNote, $sessionDateIso, $ratings): void {
                $schedule->update([
                    'status'          => 'completed',
                    'completed_at'    => now(),
                    'completed_by'    => $therapist->id,
                    'completion_note' => $completionNote,
                ]);
                $this->sessionFeedback->saveResponses($schedule, $sessionDateIso, $therapist, $ratings);
            });
        }

        $schedule->loadMissing('enrollment.child');
        if ($schedule->enrollment?->child) {
            $this->notificationService->notifySessionCompleted($schedule, $schedule->enrollment->child);
        }

        return $schedule->fresh(['startedBy', 'completedBy', 'cancelledBy']);
    }

    public function cancelSession(
        User $therapist,
        EnrollmentSchedule $schedule,
        string $sessionDateIso,
        string $cancellationReason,
    ): EnrollmentSchedule {
        $this->authorize($therapist, $schedule);

        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();

        if (! $this->portal->therapistOwnsScheduleOccurrence((int) $therapist->id, $schedule, $sessionDay->toDateString())) {
            throw ValidationException::withMessages([
                'session_date' => ['This session date does not match this appointment.'],
            ]);
        }

        $currentStatus = $this->occurrenceState->effectiveStatus($schedule, $sessionDay);
        abort_unless(
            in_array($currentStatus, ['scheduled', 'in_progress'], true),
            403,
            'Only scheduled or in-progress sessions can be cancelled.',
        );

        if ($this->occurrenceState->isRecurringTemplate($schedule)) {
            DB::transaction(function () use ($schedule, $therapist, $sessionDay, $cancellationReason): void {
                $this->occurrenceState->cancelOccurrence($therapist, $schedule, $sessionDay, $cancellationReason);
                $this->resetRecurringTemplateLifecycleFields($schedule);
            });
        } else {
            if ($schedule->session_date !== null) {
                abort_unless(
                    Carbon::parse($schedule->session_date)->startOfDay()->isSameDay($sessionDay),
                    403,
                    'This session date does not match this appointment.',
                );
            }

            DB::transaction(function () use ($schedule, $therapist, $cancellationReason): void {
                $schedule->update([
                    'status'              => 'cancelled',
                    'cancelled_at'        => now(),
                    'cancelled_by'        => $therapist->id,
                    'cancellation_reason' => $cancellationReason,
                ]);
            });
        }

        $schedule->loadMissing('enrollment.child');
        if ($schedule->enrollment?->child) {
            $this->notificationService->notifySessionCancelled($schedule, $schedule->enrollment->child);
        }

        return $schedule->fresh(['startedBy', 'completedBy', 'cancelledBy']);
    }

    /** Recurring weekly rows must stay "scheduled"; lifecycle lives on occurrence rows. */
    private function resetRecurringTemplateLifecycleFields(EnrollmentSchedule $schedule): void
    {
        if (! $this->occurrenceState->isRecurringTemplate($schedule)) {
            return;
        }

        $schedule->update([
            'status'              => 'scheduled',
            'started_at'          => null,
            'started_by'          => null,
            'completed_at'        => null,
            'completed_by'        => null,
            'completion_note'     => null,
            'cancelled_at'        => null,
            'cancelled_by'        => null,
            'cancellation_reason' => null,
            'session_notes'       => null,
        ]);
    }

    public function markNoShow(
        User $therapist,
        EnrollmentSchedule $schedule,
        string $sessionDateIso,
        ?string $notes = null,
    ): EnrollmentSchedule {
        $this->authorize($therapist, $schedule);

        $sessionDay = Carbon::parse($sessionDateIso)->startOfDay();

        if (! $this->portal->therapistOwnsScheduleOccurrence((int) $therapist->id, $schedule, $sessionDay->toDateString())) {
            throw ValidationException::withMessages([
                'session_date' => ['This session date does not match this appointment.'],
            ]);
        }

        $currentStatus = $this->occurrenceState->effectiveStatus($schedule, $sessionDay);
        abort_unless(
            in_array($currentStatus, ['scheduled', 'in_progress'], true),
            403,
            'Only scheduled or in-progress sessions can be marked no-show.',
        );

        if ($this->occurrenceState->isRecurringTemplate($schedule)) {
            DB::transaction(function () use ($schedule, $sessionDay, $notes): void {
                $this->occurrenceState->markNoShowOccurrence($schedule, $sessionDay, $notes);
                $this->resetRecurringTemplateLifecycleFields($schedule);
            });
        } else {
            if ($schedule->session_date !== null) {
                abort_unless(
                    Carbon::parse($schedule->session_date)->startOfDay()->isSameDay($sessionDay),
                    403,
                );
            }

            DB::transaction(function () use ($schedule, $notes): void {
                $schedule->update([
                    'status'        => 'no_show',
                    'session_notes' => $notes ?? $schedule->session_notes,
                ]);
            });
        }

        return $schedule->fresh();
    }

    public function updateSessionNotes(User $therapist, EnrollmentSchedule $schedule, string $notes): EnrollmentSchedule
    {
        $this->authorize($therapist, $schedule);
        $schedule->update(['session_notes' => $notes]);

        return $schedule->fresh();
    }
}
