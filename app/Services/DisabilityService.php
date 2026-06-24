<?php

namespace App\Services;

use App\Models\Disability;
use App\Repositories\Interfaces\DisabilityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class DisabilityService
{
    public function __construct(
        private readonly DisabilityRepositoryInterface $repository,
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

    public function findById(int $id): Disability
    {
        return $this->repository->findById($id) ?? abort(404, 'Present complaint not found.');
    }

    public function create(array $data, int $createdBy): Disability
    {
        return $this->repository->create(array_merge($data, ['created_by' => $createdBy, 'updated_by' => $createdBy]));
    }

    public function update(Disability $disability, array $data, int $updatedBy): Disability
    {
        return $this->repository->update($disability, array_merge($data, ['updated_by' => $updatedBy]));
    }

    public function delete(Disability $disability): bool
    {
        if (strcasecmp($disability->name, 'Other') === 0) {
            throw ValidationException::withMessages([
                'disability' => ['The "Other" present complaint cannot be deleted.'],
            ]);
        }

        return DB::transaction(function () use ($disability) {
            $disability->children()->detach();

            return $this->repository->delete($disability);
        });
    }
}
