<?php

namespace App\Repositories\Eloquent;

use App\Models\Disability;
use App\Repositories\Interfaces\DisabilityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class DisabilityRepository implements DisabilityRepositoryInterface
{
    public function findById(int $id): ?Disability
    {
        return Disability::find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = Disability::with('createdBy')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn ($q) => $q->where('name', 'like', frc_like_pattern((string) $filters['search'])))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getPublished(): Collection
    {
        return Disability::published()->orderedForPicker()->get();
    }

    public function create(array $data): Disability
    {
        return Disability::create($data);
    }

    public function update(Disability $disability, array $data): Disability
    {
        $disability->update($data);

        return $disability->refresh();
    }

    public function delete(Disability $disability): bool
    {
        return (bool) $disability->forceDelete();
    }
}
