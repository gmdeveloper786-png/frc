<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentNote;
use App\Models\User;
use App\Repositories\Interfaces\AssessmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class AssessmentService
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $repository,
        private readonly NotificationService $notificationService,
        private readonly TherapistService $therapistService,
    ) {}

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getAll($filters, $perPage);
        return $paginator->withQueryString();
    }

    public function getForChild(int $childId): Collection
    {
        return $this->repository->getForChild($childId);
    }

    public function findById(int $id): Assessment
    {
        return $this->repository->findById($id) ?? abort(404, 'Assessment not found.');
    }

    /** @return array{today:Collection, upcoming:Collection, completed:Collection, cancelled:Collection} */
    public function getTherapistAssessmentBuckets(int $therapistId): array
    {
        return $this->repository->getTherapistAssessmentBuckets($therapistId);
    }

    public function getTherapistTodayAssessments(int $therapistId): Collection
    {
        return $this->repository->getTherapistTodayAssessments($therapistId);
    }

    /**
     * @return array{upcoming_week: int, completed: int, cancelled: int}
     */
    public function getTherapistDashboardAssessmentCounts(int $therapistId): array
    {
        return $this->repository->getTherapistDashboardAssessmentCounts($therapistId);
    }

    public function getTherapistAssessmentsPaginated(int $therapistId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTherapistAssessmentsPaginated($therapistId, $filters, $perPage);
    }

    public function getTherapistUpcomingPublishedAssessments(int $therapistId, ?string $dateTo = null): Collection
    {
        return $this->repository->getTherapistUpcomingPublishedAssessments($therapistId, $dateTo);
    }

    public function create(array $data, int $createdBy): Assessment
    {
        $serviceIds = $this->normalizeIdList($data['service_ids'] ?? []);
        $childIds   = $this->normalizeIdList($data['child_ids'] ?? []);

        $payload = $this->buildAssessmentPayload($data, $createdBy, true);
        $this->assertTherapistMatchesWhenProvided($payload, $serviceIds);

        $assessment = $this->repository->create($payload, $serviceIds, $childIds);

        if ($assessment->status === 'publish') {
            $this->notificationService->notifyAssessmentPublished($assessment->fresh(['children', 'therapist']));
        }

        return $assessment;
    }

    public function update(Assessment $assessment, array $data, int $updatedBy): Assessment
    {
        $before = $this->snapshotRelations($assessment);

        $serviceIds = array_key_exists('service_ids', $data)
            ? $this->normalizeIdList($data['service_ids'])
            : $assessment->services()->pluck('services.id')->all();
        $childIds = array_key_exists('child_ids', $data)
            ? $this->normalizeIdList($data['child_ids'])
            : $assessment->children()->pluck('users.id')->all();

        $payload = $this->buildAssessmentPayload($data, $updatedBy, false, $assessment);
        // Edit form does not send service_ids; only validate branch + therapist (not stale pivot services).
        $therapistValidationServiceIds = array_key_exists('service_ids', $data)
            ? $serviceIds
            : [];
        $this->assertTherapistMatchesWhenProvided($payload, $therapistValidationServiceIds);

        $updated = $this->repository->update($assessment, $payload, $serviceIds, $childIds);

        $publishedNow = $updated->status === 'publish';
        $wasPublish   = $before['status'] === 'publish';

        if ($publishedNow && ! $wasPublish) {
            $this->notificationService->notifyAssessmentPublished($updated->fresh(['children', 'therapist']));
        } elseif ($publishedNow && $wasPublish && $this->assignmentChanged($before, $updated, $serviceIds, $childIds)) {
            $this->notificationService->notifyAssessmentUpdated($updated->fresh(['children', 'therapist']));
        }

        return $updated;
    }

    public function complete(Assessment $assessment, User $actor, array $data = []): Assessment
    {
        abort_if(! in_array($assessment->status, ['publish'], true), 403, 'Only scheduled assessments can be completed.');

        if ($actor->isTherapist()) {
            abort_unless((int) $assessment->therapist_id === (int) $actor->id, 403);
        } elseif (! $actor->hasPermission('manage_assessments')) {
            abort(403);
        }

        $notes = $data['assessment_notes'] ?? null;

        $updated = $this->repository->update(
            $assessment,
            [
                'status'            => 'completed',
                'assessment_notes'  => $notes ?? $assessment->assessment_notes,
                'completed_by'      => $actor->id,
                'completed_at'      => now(),
                'updated_by'        => $actor->id,
            ],
            $assessment->services()->pluck('services.id')->all(),
            $assessment->children()->pluck('users.id')->all(),
        );

        $this->notificationService->notifyAssessmentCompleted($updated->fresh(['children', 'therapist']));

        return $updated;
    }

    public function cancel(Assessment $assessment, User $actor, string $cancellationReason): Assessment
    {
        abort_unless($actor->hasPermission('manage_assessments'), 403);
        abort_if(in_array($assessment->status, ['completed', 'cancelled'], true), 403);

        $previousStatus = (string) $assessment->status;

        $updated = $this->repository->update(
            $assessment,
            [
                'status'                     => 'cancelled',
                'cancellation_reason'        => $cancellationReason,
                'cancelled_previous_status'  => $previousStatus,
                'cancelled_by'               => $actor->id,
                'cancelled_at'               => now(),
                'updated_by'                 => $actor->id,
            ],
            $assessment->services()->pluck('services.id')->all(),
            $assessment->children()->pluck('users.id')->all(),
        );

        if ($previousStatus === 'publish') {
            $this->notificationService->notifyAssessmentCancelled($updated->fresh(['children', 'therapist']));
        }

        return $updated;
    }

    public function addAssessmentNote(Assessment $assessment, array $data, User $actor): AssessmentNote
    {
        abort_unless($actor->isTherapist(), 403);
        abort_unless((int) $assessment->therapist_id === (int) $actor->id, 403);
        abort_if(! in_array($assessment->status, ['publish'], true), 403);

        $note = AssessmentNote::create([
            'assessment_id'           => $assessment->id,
            'therapist_id'            => (int) $actor->id,
            'child_id'                => $data['child_id'] ?? null,
            'observation'             => $data['observation'] ?? null,
            'recommended_services'    => $data['recommended_services'] ?? null,
            'child_response'          => $data['child_response'] ?? null,
            'initial_recommendation'  => $data['initial_recommendation'] ?? null,
            'additional_notes'        => $data['additional_notes'] ?? null,
            'status'                  => $data['status'] ?? 'draft',
            'created_by'              => $actor->id,
            'updated_by'              => $actor->id,
        ]);

        return $note;
    }

    public function updateAssessmentNote(AssessmentNote $note, array $data, User $actor): AssessmentNote
    {
        abort_unless($actor->isTherapist(), 403);
        abort_unless((int) $note->therapist_id === (int) $actor->id, 403);

        $note->update([
            'observation'             => array_key_exists('observation', $data) ? $data['observation'] : $note->observation,
            'recommended_services'    => array_key_exists('recommended_services', $data) ? $data['recommended_services'] : $note->recommended_services,
            'child_response'          => array_key_exists('child_response', $data) ? $data['child_response'] : $note->child_response,
            'initial_recommendation'  => array_key_exists('initial_recommendation', $data) ? $data['initial_recommendation'] : $note->initial_recommendation,
            'additional_notes'        => array_key_exists('additional_notes', $data) ? $data['additional_notes'] : $note->additional_notes,
            'status'                  => $data['status'] ?? $note->status,
            'child_id'                => array_key_exists('child_id', $data) ? $data['child_id'] : $note->child_id,
            'updated_by'              => $actor->id,
        ]);

        return $note->fresh();
    }

    public function deleteAssessmentNote(AssessmentNote $note, User $actor): void
    {
        abort_unless($actor->isTherapist(), 403);
        abort_unless((int) $note->therapist_id === (int) $actor->id, 403);

        $note->delete();
    }

    public function delete(Assessment $assessment): bool
    {
        return DB::transaction(function () use ($assessment) {
            AssessmentNote::withTrashed()
                ->where('assessment_id', $assessment->id)
                ->each(fn (AssessmentNote $note) => $note->forceDelete());

            $assessment->services()->detach();
            $assessment->children()->detach();

            return $this->repository->delete($assessment);
        });
    }

    private function buildAssessmentPayload(array $data, int $userId, bool $isCreate, ?Assessment $existing = null): array
    {
        $date = $data['date'] ?? ($existing?->date?->format('Y-m-d'));
        $day  = \Carbon\Carbon::parse($date)->format('l');

        $time = $data['time'] ?? $existing?->time;
        if ($time !== null) {
            $time = \Carbon\Carbon::parse($time)->format('H:i');
        }

        $payload = [
            'date'          => $date,
            'day'           => $day,
            'time'          => $time,
            'branch_id'     => $data['branch_id'] ?? $existing?->branch_id,
            'therapist_id'  => array_key_exists('therapist_id', $data)
                ? ($data['therapist_id'] !== null && $data['therapist_id'] !== '' ? (int) $data['therapist_id'] : null)
                : $existing?->therapist_id,
            'status'        => $data['status'] ?? $existing?->status,
            'updated_by'    => $userId,
        ];

        if ($isCreate) {
            $payload['created_by'] = $userId;
        }

        return $payload;
    }

    private function assertTherapistMatchesWhenProvided(array $payload, array $serviceIds): void
    {
        $tid = $payload['therapist_id'] ?? null;
        if (! $tid) {
            return;
        }

        $therapist = User::find((int) $tid);
        abort_if(! $therapist, 422, 'Invalid therapist.');

        $branchId = (int) $payload['branch_id'];
        abort_unless(
            $this->therapistService->therapistQualifiesForFilters($therapist, $branchId, [], 'any'),
            422,
            'Therapist does not belong to the selected branch.'
        );

        if ($serviceIds !== []) {
            abort_unless(
                $this->therapistService->therapistQualifiesForFilters($therapist, $branchId, $serviceIds, 'any'),
                422,
                'Selected therapist does not offer any of the required services for this assessment.'
            );
        }
    }

    /** @param  array<int|string>  $ids */
    private function normalizeIdList(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /** @return array{status:string, therapist_id:?int, branch_id:?int, date:?string, time:?string, service_ids:array, child_ids:array} */
    private function snapshotRelations(Assessment $assessment): array
    {
        return [
            'status'        => $assessment->status,
            'therapist_id'  => $assessment->therapist_id,
            'branch_id'     => $assessment->branch_id,
            'date'          => $assessment->date?->format('Y-m-d'),
            'time'          => $assessment->time,
            'service_ids'   => $assessment->services()->pluck('services.id')->sort()->values()->all(),
            'child_ids'     => $assessment->children()->pluck('users.id')->sort()->values()->all(),
        ];
    }

    private function assignmentChanged(array $before, Assessment $updated, array $serviceIds, array $childIds): bool
    {
        $sSorted = $serviceIds;
        sort($sSorted);
        $cSorted = $childIds;
        sort($cSorted);

        return $before['therapist_id'] != $updated->therapist_id
            || $sSorted !== $before['service_ids']
            || $cSorted !== $before['child_ids']
            || $before['date'] !== $updated->date?->format('Y-m-d')
            || $before['time'] !== $updated->time;
    }
}
