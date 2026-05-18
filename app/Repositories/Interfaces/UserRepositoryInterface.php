<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function getChildren(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getApprovedChildren(): Collection;

    /** @param  array<int, int>  $ids */
    public function getApprovedChildrenByIds(array $ids): Collection;

    public function searchApprovedChildren(string $search, int $limit = 40): Collection;
    public function getPendingChildren(int $perPage = 15): LengthAwarePaginator;
    public function getUsersByRole(string $role): Collection;
}
