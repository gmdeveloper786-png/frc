<?php

namespace App\Repositories\Interfaces;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BranchRepositoryInterface
{
    public function findById(int $id): ?Branch;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getPublished(): Collection;
    public function create(array $data): Branch;
    public function update(Branch $branch, array $data): Branch;
    public function delete(Branch $branch): bool;
}
