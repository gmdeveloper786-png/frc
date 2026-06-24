<?php

namespace App\Services;

use App\Models\Service;
use App\Repositories\Interfaces\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class ServiceManagementService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
        private readonly SessionFeedbackService $sessionFeedback,
    ) {}

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getAll($filters, $perPage);
        return $paginator->withQueryString();
    }

    public function getPublished(): Collection
    {
        return $this->repository->getPublished();
    }

    public function findById(int $id): Service
    {
        return $this->repository->findById($id) ?? abort(404, 'Service not found.');
    }

    public function create(array $data, int $createdBy): Service
    {
        $questions = $data['feedback_questions'] ?? [];
        unset($data['feedback_questions']);

        $service = $this->repository->create(array_merge($data, ['created_by' => $createdBy, 'updated_by' => $createdBy]));
        $this->sessionFeedback->syncServiceQuestions($service, $questions, $createdBy);

        return $service->fresh(['feedbackQuestions']);
    }

    public function update(Service $service, array $data, int $updatedBy): Service
    {
        $questions = $data['feedback_questions'] ?? [];
        unset($data['feedback_questions']);

        $service = $this->repository->update($service, array_merge($data, ['updated_by' => $updatedBy]));
        $this->sessionFeedback->syncServiceQuestions($service, $questions, $updatedBy);

        return $service->fresh(['feedbackQuestions']);
    }

    public function delete(Service $service): bool
    {
        return DB::transaction(function () use ($service) {
            $this->sessionFeedback->purgeForServiceDeletion($service);
            $service->therapists()->detach();
            $service->assessments()->detach();

            return $this->repository->delete($service);
        });
    }
}
