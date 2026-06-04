<?php

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class BranchRepository implements BranchRepositoryInterface
{
    public function findById(int $id): ?Branch
    {
        return Branch::find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Branch::with('createdBy')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('city', 'like', '%' . $filters['search'] . '%');
            }))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getPublished(): Collection
    {
        return Branch::published()->forDropdown()->orderedForDropdown()->get();
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    public function delete(Branch $branch): bool
    {
        return $branch->delete();
    }
}
