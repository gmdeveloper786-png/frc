<?php

namespace App\Services;

use App\Models\EnrollmentSchedule;
use App\Models\ProgressNote;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Collection as SupportCollection;

class TherapistProgressNoteService
{
    public function __construct(
        private readonly TherapistPortalService $portal,
        private readonly NotificationService $notificationService,
    ) {}

    public function paginateForTherapist(int $therapistId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = ProgressNote::query()
            ->where('therapist_id', $therapistId)
            ->with(['child', 'service', 'createdBy'])
            ->when(! empty($filters['child_id']), fn($q) => $q->where('child_id', (int) $filters['child_id']))
            ->when(! empty($filters['service_id']), fn($q) => $q->where('service_id', (int) $filters['service_id']))
            ->when(! empty($filters['status']), fn($q) => $q->where('status', (string) $filters['status']))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    /**
     * Services for the progress-note form: therapist pivot plus enrollment/pending session services.
     *
     * @param  SupportCollection<int, array<string, mixed>>|null  $pendingRows
     * @return Collection<int, Service>
     */
    public function serviceOptionsForProgressNoteForm(
        ?User $therapist,
        ?int $includeServiceId = null,
        ?SupportCollection $pendingRows = null,
    ): Collection {
        $options = $therapist?->therapistServices
            ? $therapist->therapistServices->sortBy('name')->values()
            : new Collection;

        $extraIds = collect();
        if ($includeServiceId !== null && $includeServiceId > 0) {
            $extraIds->push($includeServiceId);
        }
        if ($pendingRows !== null) {
            foreach ($pendingRows as $row) {
                $sid = $row['schedule']->enrollment?->service_id ?? null;
                if ($sid !== null) {
                    $extraIds->push((int) $sid);
                }
            }
        }

        $existingIds = $options->pluck('id')->map(fn ($id) => (int) $id)->all();
        $missingIds = $extraIds->unique()->filter(fn (int $id) => $id > 0 && ! in_array($id, $existingIds, true));

        if ($missingIds->isNotEmpty()) {
            $extras = Service::query()
                ->whereIn('id', $missingIds->all())
                ->orderBy('name')
                ->get();
            $options = $options->concat($extras)->sortBy('name')->values();
        }

        return new Collection($options->all());
    }

    /**
     * @return Collection<int, Service>
     */
    public function servicesForProgressNoteFilter(int $therapistId): Collection
    {
        $ids = ProgressNote::query()
            ->where('therapist_id', $therapistId)
            ->whereNotNull('service_id')
            ->distinct()
            ->pluck('service_id');

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Service::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(User $therapist, array $data): ProgressNote
    {
        $childId = (int) $data['child_id'];
        abort_unless($this->portal->therapistHasAccessToChild($therapist->id, $childId), 403);

        $scheduleId = (int) ($data['enrollment_schedule_id'] ?? 0);
        abort_if($scheduleId <= 0, 422);

        $scheduleRow = EnrollmentSchedule::query()->with('enrollment')->find($scheduleId);
        abort_if($scheduleRow === null || $scheduleRow->enrollment === null, 422);

        abort_unless((int) $scheduleRow->therapist_id === (int) $therapist->id, 403);
        abort_unless($scheduleRow->status === 'completed', 422);
        abort_unless((int) $scheduleRow->enrollment->child_id === $childId, 422);

        $sessionDateIso = Carbon::parse((string) $data['session_date'])->toDateString();
        abort_unless(
            $this->portal->occurrenceBelongsToTherapistCompletedSchedule($therapist->id, $scheduleId, $sessionDateIso),
            422,
        );

        $note = ProgressNote::create([
            'child_id'               => $childId,
            'therapist_id'           => $therapist->id,
            'enrollment_id'          => $data['enrollment_id'] ?? $scheduleRow->enrollment_id,
            'enrollment_schedule_id' => $scheduleId,
            'service_id'             => $data['service_id'] ?? $scheduleRow->enrollment->service_id ?? null,
            'session_date'           => $sessionDateIso,
            'therapy_goal'           => $data['therapy_goal'] ?? null,
            'notes'                  => $data['notes'],
            'child_response'         => $data['child_response'] ?? null,
            'progress_level'         => $data['progress_level'],
            'parent_instructions'    => $data['parent_instructions'] ?? null,
            'next_plan'              => $data['next_plan'] ?? null,
            'status'                 => $data['status'],
            'created_by'             => $therapist->id,
            'updated_by'             => $therapist->id,
        ]);

        $this->notificationService->notifyProgressNoteAdded($note->loadMissing('child'));
        $this->portal->forgetPendingDocumentationCache((int) $therapist->id);

        return $note;
    }

    public function update(User $therapist, ProgressNote $note, array $data): ProgressNote
    {
        abort_unless((int) $note->therapist_id === (int) $therapist->id, 403);

        $beforeStatus = $note->status;
        $newStatus = $data['status'];

        $note->update([
            'therapy_goal'        => $data['therapy_goal'] ?? $note->therapy_goal,
            'notes'               => $data['notes'],
            'child_response'      => $data['child_response'] ?? $note->child_response,
            'progress_level'      => $data['progress_level'],
            'parent_instructions' => $data['parent_instructions'] ?? $note->parent_instructions,
            'next_plan'           => $data['next_plan'] ?? $note->next_plan,
            'status'              => $newStatus,
            'updated_by'          => $therapist->id,
        ]);

        if ($beforeStatus !== 'completed' && $newStatus === 'completed') {
            $this->notificationService->notifyProgressNoteCompleted($note->fresh(['child']));
        }

        $this->portal->forgetPendingDocumentationCache((int) $therapist->id);

        return $note->fresh();
    }

    public function delete(User $therapist, ProgressNote $note): void
    {
        abort_unless((int) $note->therapist_id === (int) $therapist->id, 403);

        $note->delete();

        $this->portal->forgetPendingDocumentationCache((int) $therapist->id);
    }
}
