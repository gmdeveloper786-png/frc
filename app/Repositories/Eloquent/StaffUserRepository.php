<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class StaffUserRepository
{
    private const STAFF_ROLE_NAMES = [Role::ADMIN, Role::FINANCE, Role::APPROVAL_DISCOUNT];

    /**
     * @param  array{search?:string, role?:string, status?:string}  $filters
     */
    public function getStaffUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $role    = isset($filters['role']) ? trim((string) $filters['role']) : '';
        $status  = isset($filters['status']) ? trim((string) $filters['status']) : '';

        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = User::query()
            ->with(['role', 'branch'])
            ->whereHas('role', fn($q) => $q->whereIn('name', self::STAFF_ROLE_NAMES))
            ->when($search !== '', function ($q) use ($search) {
                $like = frc_like_pattern($search);
                $q->where(function ($inner) use ($like) {
                    $inner->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone_number', 'like', $like);
                });
            })
            ->when($role !== '' && in_array($role, self::STAFF_ROLE_NAMES, true), function ($q) use ($role) {
                $q->whereHas('role', fn($r) => $r->where('name', $role));
            })
            ->when($status !== '' && in_array($status, ['active', 'inactive'], true), function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data  Must include role_id, password (hashed), status, created_by, optional fields
     */
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh(['role']);
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function isStaffUser(User $user): bool
    {
        return in_array($user->role?->name, self::STAFF_ROLE_NAMES, true);
    }
}
