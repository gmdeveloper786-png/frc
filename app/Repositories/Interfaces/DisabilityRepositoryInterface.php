<?php

namespace App\Repositories\Interfaces;

use App\Models\Disability;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DisabilityRepositoryInterface
{
    public function findById(int $id): ?Disability;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getPublished(): Collection;
    public function create(array $data): Disability;
    public function update(Disability $disability, array $data): Disability;
    public function delete(Disability $disability): bool;
}
