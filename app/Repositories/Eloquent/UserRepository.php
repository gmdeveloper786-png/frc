<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with('role')->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::with('role')->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh('role');
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getChildren(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = User::with(['role', 'disabilities'])
            ->children()
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('full_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone_number', 'like', '%' . $filters['search'] . '%');
            }))
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getApprovedChildren(): Collection
    {
        return User::children()->approved()->orderBy('full_name')->get();
    }

    public function getApprovedChildrenByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return new Collection();
        }

        return User::children()
            ->approved()
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone_number', 'gender', 'date_of_birth']);
    }

    public function searchApprovedChildren(string $search, int $limit = 40): Collection
    {
        $search = trim($search);
        $limit = min(50, max(1, $limit));

        $query = User::children()->approved()->orderBy('full_name');

        if (ctype_digit($search)) {
            $id = (int) $search;
            $query->where(function ($q) use ($search, $id): void {
                $q->where('id', $id)
                    ->orWhere('full_name', 'like', '%' . $search . '%');
            });
        } else {
            $query->where('full_name', 'like', '%' . $search . '%');
        }

        return $query->limit($limit)->get(['id', 'full_name', 'phone_number', 'gender', 'date_of_birth']);
    }

    public function getPendingChildren(int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = User::with(['disabilities'])
            ->children()
            ->pending()
            ->latest()
            ->paginate($perPage);

        return $paginator->withQueryString();
    }

    public function getUsersByRole(string $role): Collection
    {
        return User::with('role')
            ->byRole($role)
            ->whereIn('status', ['active', 'approved'])
            ->orderBy('full_name')
            ->get();
    }
}
