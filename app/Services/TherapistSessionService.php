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

        $timeSlot = (string) $schedule->time_slot;
        if (! SessionTimeSlotParser::isWithinStartWindow($sessionDay, $timeSlot)) {
            $startsAt = SessionTimeSlotParser::occurrenceStart($sessionDay, $timeSlot);
            $endsAt = SessionTimeSlotParser::occurrenceEnd($sessionDay, $timeSlot);
            if (now()->lt($startsAt)) {
                throw ValidationException::withMessages([
                    'session_date' => ['You can start this session only at or after its scheduled date and start time.'],
                ]);
            }
            throw ValidationException::withMessages([
                'session_date' => ['The scheduled time for this session has passed. You can no longer start it.'],
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

    public function completeSession(User $therapist, EnrollmentSchedule $schedule, string $sessionDateIso, ?string $completionNote = null): EnrollmentSchedule
    {
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

        $startsAt = SessionTimeSlotParser::occurrenceStart($sessionDay, (string) $schedule->time_slot);
        if (now()->lt($startsAt)) {
            throw ValidationException::withMessages([
                'session_date' => ['You can complete this session only at or after its scheduled date and start time.'],
            ]);
        }

        if ($this->occurrenceState->isRecurringTemplate($schedule)) {
            DB::transaction(function () use ($schedule, $therapist, $sessionDay, $completionNote): void {
                $this->occurrenceState->completeOccurrence($therapist, $schedule, $sessionDay, $completionNote);
            });
        } else {
            DB::transaction(function () use ($schedule, $therapist, $completionNote): void {
                $schedule->update([
                    'status'          => 'completed',
                    'completed_at'    => now(),
                    'completed_by'    => $therapist->id,
                    'completion_note' => $completionNote,
                ]);
            });
        }

        $schedule->loadMissing('enrollment.child');
        if ($schedule->enrollment?->child) {
            $this->notificationService->notifySessionCompleted($schedule, $schedule->enrollment->child);
        }

        $this->portal->forgetPendingDocumentationCache((int) $therapist->id);

        return $schedule->fresh(['startedBy', 'completedBy', 'cancelledBy']);
    }

    public function cancelSession(User $therapist, EnrollmentSchedule $schedule, string $cancellationReason): EnrollmentSchedule
    {
        $this->authorize($therapist, $schedule);
        abort_unless(! in_array($schedule->status, ['completed', 'cancelled', 'no_show'], true), 403);

        DB::transaction(function () use ($schedule, $therapist, $cancellationReason): void {
            $schedule->update([
                'status'               => 'cancelled',
                'cancelled_at'         => now(),
                'cancelled_by'         => $therapist->id,
                'cancellation_reason'  => $cancellationReason,
            ]);
        });

        $schedule->loadMissing('enrollment.child');
        if ($schedule->enrollment?->child) {
            $this->notificationService->notifySessionCancelled($schedule, $schedule->enrollment->child);
        }

        return $schedule->fresh(['startedBy', 'completedBy', 'cancelledBy']);
    }

    public function markNoShow(User $therapist, EnrollmentSchedule $schedule, ?string $notes = null): EnrollmentSchedule
    {
        $this->authorize($therapist, $schedule);
        abort_unless(! in_array($schedule->status, ['completed', 'cancelled', 'no_show'], true), 403);

        DB::transaction(function () use ($schedule, $notes): void {
            $schedule->update([
                'status'        => 'no_show',
                'session_notes' => $notes ?? $schedule->session_notes,
            ]);
        });

        return $schedule->fresh();
    }

    public function updateSessionNotes(User $therapist, EnrollmentSchedule $schedule, string $notes): EnrollmentSchedule
    {
        $this->authorize($therapist, $schedule);
        $schedule->update(['session_notes' => $notes]);

        return $schedule->fresh();
    }
}
