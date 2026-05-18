<?php

namespace App\Services;

use App\Models\EnrollmentSchedule;
use App\Models\ProgressNote;
use App\Models\User;
use App\Services\SessionTimeSlotParser;
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
     * Progress notes for this calendar occurrence (schedule template + session_date).
     *
     * @return Collection<int, ProgressNote>
     */
    public function progressNotesForOccurrence(EnrollmentSchedule $schedule, Carbon $occurrenceDate): Collection
    {
        return ProgressNote::query()
            ->where('enrollment_schedule_id', $schedule->id)
            ->whereDate('session_date', $occurrenceDate->toDateString())
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Progress-note ids/status flags for many schedule occurrences in one query.
     *
     * @param  Collection<int, array{schedule: EnrollmentSchedule, effective_date: Carbon}>  $rows
     * @return array<string, array{draft_id: ?int, completed_id: ?int, has_completed: bool, has_draft: bool}>
     */
    public function progressMetaIndexForSessionRows(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $scheduleIds = $rows
            ->map(fn(array $row): int => (int) $row['schedule']->id)
            ->unique()
            ->values()
            ->all();

        $dates = $rows
            ->map(fn(array $row): string => $row['effective_date']->toDateString())
            ->unique()
            ->sort()
            ->values();

        $notes = ProgressNote::query()
            ->whereIn('enrollment_schedule_id', $scheduleIds)
            ->whereDate('session_date', '>=', $dates->first())
            ->whereDate('session_date', '<=', $dates->last())
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get(['id', 'enrollment_schedule_id', 'session_date', 'status']);

        $index = [];
        foreach ($notes as $note) {
            $dateKey = $note->session_date instanceof Carbon
                ? $note->session_date->toDateString()
                : Carbon::parse((string) $note->session_date)->toDateString();
            $key = $note->enrollment_schedule_id . '|' . $dateKey;

            if (! isset($index[$key])) {
                $index[$key] = [
                    'draft_id'      => null,
                    'completed_id'  => null,
                    'has_completed' => false,
                    'has_draft'     => false,
                ];
            }

            if ($note->status === 'draft' && $index[$key]['draft_id'] === null) {
                $index[$key]['draft_id'] = $note->id;
                $index[$key]['has_draft'] = true;
            }
            if ($note->status === 'completed' && $index[$key]['completed_id'] === null) {
                $index[$key]['completed_id'] = $note->id;
                $index[$key]['has_completed'] = true;
            }
        }

        return $index;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, array{draft_id: ?int, completed_id: ?int, has_completed: bool, has_draft: bool}>  $progressMetaIndex
     * @return Collection<int, array<string, mixed>>
     */
    public function enrichTherapistSessionListRows(Collection $rows, array $progressMetaIndex): Collection
    {
        return $rows->map(function (array $row) use ($progressMetaIndex): array {
            $sch = $row['schedule'];
            $occ = $row['effective_date'];
            $metaKey = $sch->id . '|' . $occ->toDateString();
            $meta = $progressMetaIndex[$metaKey] ?? [
                'draft_id'      => null,
                'completed_id'  => null,
                'has_completed' => false,
                'has_draft'     => false,
            ];
            $row['progress_meta'] = $meta;

            $sessionDay = $occ->copy()->startOfDay();
            $timeSlot = (string) $sch->time_slot;
            $startsAt = SessionTimeSlotParser::occurrenceStart($sessionDay, $timeSlot);
            $endsAt = SessionTimeSlotParser::occurrenceEnd($sessionDay, $timeSlot);
            $row['occurrence_starts_at'] = $startsAt;
            $row['occurrence_ends_at'] = $endsAt;
            $occStatus = (string) ($row['status'] ?? $sch->status);
            $row['can_start_session_now'] = $occStatus === 'scheduled'
                && SessionTimeSlotParser::isWithinStartWindow($sessionDay, $timeSlot);
            $row['session_start_window_passed'] = $occStatus === 'scheduled' && now()->greaterThanOrEqualTo($endsAt);

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

        $notes = $this->progressNotesForOccurrence($schedule, $occurrence);
        $draft = $notes->firstWhere('status', 'draft');
        $completed = $notes->firstWhere('status', 'completed');

        $progressStatus = 'none';
        if ($draft) {
            $progressStatus = 'draft';
        } elseif ($completed) {
            $progressStatus = 'completed';
        }

        $primaryPn = $draft ?? $completed;

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
            'occurrence_date_iso' => $occurrence->toDateString(),
            'session_date_label'  => $occurrence->format('l, d M Y'),
            'day_label'           => $occurrence->format('l'),
            'time_slot'           => (string) $schedule->time_slot,
            'child_name'          => $schedule->enrollment?->child?->full_name ?? '—',
            'therapist_name'      => $schedule->therapist?->full_name ?? '—',
            'service_name'        => $schedule->enrollment?->service?->name ?? '—',
            'branch_name'         => $schedule->branch?->name ?? $schedule->enrollment?->branch?->name ?? '—',
            'status'              => $status,
            'status_label'        => $this->statusLabel($status),
            'child_id'            => $schedule->enrollment?->child_id,
            'enrollment_id'       => $schedule->enrollment_id,
            'service_id'          => $schedule->enrollment?->service_id,
            'enrollment_schedule_id' => $schedule->id,
            'started_at'          => ($occurrenceRow?->started_at ?? $schedule->started_at)?->toIso8601String(),
            'started_by_name'     => $occurrenceRow?->startedBy?->full_name ?? $schedule->startedBy?->full_name,
            'completed_at'        => ($occurrenceRow?->completed_at ?? $schedule->completed_at)?->toIso8601String(),
            'completed_by_name'   => $occurrenceRow?->completedBy?->full_name ?? $schedule->completedBy?->full_name,
            'completion_note'     => $completionDisplay,
            'cancelled_at'        => ($occurrenceRow?->cancelled_at ?? $schedule->cancelled_at)?->toIso8601String(),
            'cancelled_by_name'   => $occurrenceRow?->cancelledBy?->full_name ?? $schedule->cancelledBy?->full_name,
            'cancellation_reason' => $cancellationDisplay,
            'progress_note_status' => $progressStatus,
            'progress_note_draft_id' => $draft?->id,
            'progress_note_completed_id' => $completed?->id,
            'progress_note_preview' => $primaryPn ? [
                'progress_level' => ProgressNote::labelForProgressLevel($primaryPn->progress_level),
                'therapy_goal'   => $primaryPn->therapy_goal,
                'notes_excerpt'  => \Illuminate\Support\Str::limit(strip_tags((string) ($primaryPn->notes ?? '')), 280),
            ] : null,
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
