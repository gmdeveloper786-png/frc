<?php

namespace App\Repositories\Interfaces;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ServiceRepositoryInterface
{
    public function findById(int $id): ?Service;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getPublished(): Collection;
    public function create(array $data): Service;
    public function update(Service $service, array $data): Service;
    public function delete(Service $service): bool;
}
