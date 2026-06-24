<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Repositories\Interfaces\TherapistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class TherapistRepository implements TherapistRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['role', 'therapistProfile.branch', 'therapistServices'])
            ->byRole(Role::THERAPIST)
            ->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = User::with(['role', 'therapistProfile.branch', 'therapistServices'])
            ->byRole(Role::THERAPIST)
            ->when(isset($filters['branch_id']), fn($q) => $q->whereHas('therapistProfile', fn($p) => $p->where('branch_id', $filters['branch_id'])))
            ->when(isset($filters['status']), fn($q) => $q->whereHas('therapistProfile', fn($p) => $p->where('status', $filters['status'])))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($q) use ($filters) {
                $like = frc_like_pattern((string) $filters['search']);
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            }))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getByBranch(int $branchId, array $serviceIds = [], string $serviceMatch = 'all'): Collection
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        $serviceMatch = $serviceMatch === 'any' ? 'any' : 'all';

        $q = User::with(['therapistProfile.branch', 'therapistServices'])
            ->byRole(Role::THERAPIST)
            ->where('status', 'active')
            ->whereHas('therapistProfile', fn($q) => $q->where('branch_id', $branchId)->where('status', 'active'));

        if ($serviceIds !== []) {
            if ($serviceMatch === 'any') {
                $q->whereHas('therapistServices', fn($sq) => $sq->whereIn('services.id', $serviceIds));
            } else {
                foreach ($serviceIds as $sid) {
                    $q->whereHas('therapistServices', fn($sq) => $sq->where('services.id', $sid));
                }
            }
        }

        return $q->orderBy('full_name')->get();
    }

    public function create(array $userData, array $profileData, array $serviceIds): User
    {
        return DB::transaction(function () use ($userData, $profileData, $serviceIds) {
            $user = User::create($userData);
            TherapistProfile::create(array_merge($profileData, ['user_id' => $user->id]));
            $user->therapistServices()->sync($serviceIds);

            return $user->load(['therapistProfile.branch', 'therapistServices']);
        });
    }

    public function update(User $therapist, array $userData, array $profileData, array $serviceIds): User
    {
        return DB::transaction(function () use ($therapist, $userData, $profileData, $serviceIds) {
            $therapist->update($userData);
            $therapist->therapistProfile()->updateOrCreate(['user_id' => $therapist->id], $profileData);
            $therapist->therapistServices()->sync($serviceIds);

            return $therapist->fresh(['therapistProfile.branch', 'therapistServices']);
        });
    }

    public function delete(User $therapist): bool
    {
        return DB::transaction(function () use ($therapist) {
            $therapist->therapistServices()->detach();
            $therapist->therapistProfile?->forceDelete();

            return (bool) $therapist->forceDelete();
        });
    }
}
