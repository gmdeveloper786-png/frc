<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TherapistRepositoryInterface
{
    public function findById(int $id): ?User;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    /**
     * @param  array<int>  $serviceIds
     * @param  'all'|'any'  $serviceMatch  all = therapist must have every service; any = at least one overlap (for assessments)
     */
    public function getByBranch(int $branchId, array $serviceIds = [], string $serviceMatch = 'all'): Collection;
    public function create(array $userData, array $profileData, array $serviceIds): User;
    public function update(User $therapist, array $userData, array $profileData, array $serviceIds): User;
    public function delete(User $therapist): bool;
}
