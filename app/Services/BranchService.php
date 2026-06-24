<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class BranchService
{
    public function __construct(
        private readonly BranchRepositoryInterface $repository,
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

    public function findById(int $id): Branch
    {
        return $this->repository->findById($id) ?? abort(404, 'Branch not found.');
    }

    public function create(array $data, int $createdBy): Branch
    {
        return $this->repository->create(array_merge($data, ['created_by' => $createdBy, 'updated_by' => $createdBy]));
    }

    public function update(Branch $branch, array $data, int $updatedBy): Branch
    {
        return $this->repository->update($branch, array_merge($data, ['updated_by' => $updatedBy]));
    }

    public function delete(Branch $branch): bool
    {
        return DB::transaction(fn () => $this->repository->delete($branch));
    }
}
