<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentSchedule;
use App\Models\EnrollmentScheduleOccurrence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Per-calendar-date session state for recurring weekly schedule templates.
 */
class SessionOccurrenceStateService
{
    public function isRecurringTemplate(EnrollmentSchedule $schedule): bool
    {
        $schedule->loadMissing('enrollment');

        return $schedule->session_date === null
            && $schedule->enrollment instanceof Enrollment
            && (bool) $schedule->enrollment->repeat_weekly;
    }

    public function effectiveStatus(EnrollmentSchedule $schedule, Carbon $occurrenceDate): string
    {
        if ($this->isRecurringTemplate($schedule)) {
            $occurrence = $this->findOccurrence($schedule, $occurrenceDate);

            return $occurrence?->status ?? 'scheduled';
        }

        if ($schedule->session_date !== null) {
            $slotDay = Carbon::parse($schedule->session_date)->startOfDay();
            if (! $occurrenceDate->isSameDay($slotDay)) {
                return 'scheduled';
            }
        }

        return (string) $schedule->status;
    }

    public function findOccurrence(EnrollmentSchedule $schedule, Carbon $occurrenceDate): ?EnrollmentScheduleOccurrence
    {
        return EnrollmentScheduleOccurrence::query()
            ->where('enrollment_schedule_id', $schedule->id)
            ->whereDate('occurrence_date', $occurrenceDate->toDateString())
            ->first();
    }

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $rows
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function attachEffectiveStatuses(SupportCollection $rows): SupportCollection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $recurringScheduleIds = $rows
            ->filter(fn (array $row): bool => $this->isRecurringTemplate($row['schedule']))
            ->pluck('schedule.id')
            ->unique()
            ->values();

        $occurrencesBySchedule = $recurringScheduleIds->isEmpty()
            ? collect()
            : EnrollmentScheduleOccurrence::query()
                ->whereIn('enrollment_schedule_id', $recurringScheduleIds->all())
                ->get()
                ->groupBy('enrollment_schedule_id');

        return $rows->map(function (array $row) use ($occurrencesBySchedule): array {
            /** @var EnrollmentSchedule $schedule */
            $schedule = $row['schedule'];
            /** @var Carbon $effectiveDate */
            $effectiveDate = $row['effective_date'];

            if ($this->isRecurringTemplate($schedule)) {
                $occurrence = $occurrencesBySchedule
                    ->get($schedule->id, collect())
                    ->first(fn (EnrollmentScheduleOccurrence $o): bool => $o->occurrence_date->isSameDay($effectiveDate));

                $row['status'] = $occurrence?->status ?? 'scheduled';
                $row['occurrence'] = $occurrence;
            } else {
                $row['status'] = $this->effectiveStatus($schedule, $effectiveDate);
                $row['occurrence'] = null;
            }

            return $row;
        });
    }

    public function startOccurrence(
        User $therapist,
        EnrollmentSchedule $schedule,
        Carbon $occurrenceDate,
    ): EnrollmentScheduleOccurrence {
        return EnrollmentScheduleOccurrence::query()->updateOrCreate(
            [
                'enrollment_schedule_id' => $schedule->id,
                'occurrence_date'        => $occurrenceDate->toDateString(),
            ],
            [
                'status'     => 'in_progress',
                'started_at' => now(),
                'started_by' => $therapist->id,
            ],
        );
    }

    public function completeOccurrence(
        User $therapist,
        EnrollmentSchedule $schedule,
        Carbon $occurrenceDate,
        ?string $completionNote = null,
    ): EnrollmentScheduleOccurrence {
        $occurrence = $this->findOccurrence($schedule, $occurrenceDate);
        abort_if($occurrence === null || $occurrence->status !== 'in_progress', 403, 'Only in-progress sessions can be completed.');

        $occurrence->update([
            'status'          => 'completed',
            'completed_at'    => now(),
            'completed_by'    => $therapist->id,
            'completion_note' => $completionNote,
        ]);

        return $occurrence->fresh();
    }

    public function cancelOccurrence(
        User $therapist,
        EnrollmentSchedule $schedule,
        Carbon $occurrenceDate,
        string $cancellationReason,
    ): EnrollmentScheduleOccurrence {
        return EnrollmentScheduleOccurrence::query()->updateOrCreate(
            [
                'enrollment_schedule_id' => $schedule->id,
                'occurrence_date'        => $occurrenceDate->toDateString(),
            ],
            [
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancelled_by'        => $therapist->id,
                'cancellation_reason' => $cancellationReason,
            ],
        );
    }

    public function markNoShowOccurrence(
        EnrollmentSchedule $schedule,
        Carbon $occurrenceDate,
        ?string $notes = null,
    ): EnrollmentScheduleOccurrence {
        return EnrollmentScheduleOccurrence::query()->updateOrCreate(
            [
                'enrollment_schedule_id' => $schedule->id,
                'occurrence_date'        => $occurrenceDate->toDateString(),
            ],
            [
                'status'        => 'no_show',
                'session_notes' => $notes,
            ],
        );
    }

    /** Reset recurring templates that incorrectly stored series-wide status on the template row. */
    public function normalizeRecurringTemplateRows(): void
    {
        EnrollmentSchedule::query()
            ->whereNull('session_date')
            ->whereIn('status', ['in_progress', 'completed', 'cancelled', 'no_show'])
            ->whereHas('enrollment', fn ($q) => $q->where('repeat_weekly', true))
            ->update([
                'status'              => 'scheduled',
                'started_at'          => null,
                'started_by'          => null,
                'completed_at'        => null,
                'completed_by'        => null,
                'completion_note'     => null,
                'cancelled_at'        => null,
                'cancelled_by'        => null,
                'cancellation_reason' => null,
            ]);
    }
}
