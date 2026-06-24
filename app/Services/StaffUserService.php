<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\Eloquent\StaffUserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class StaffUserService
{
    public function __construct(
        private readonly StaffUserRepository $repository,
        private readonly UserNotificationService $notifications,
    ) {}

    /**
     * @param  array{search?:string, role?:string, status?:string}  $filters
     */
    public function getStaffUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $this->repository->getStaffUsers($filters, $perPage);
        return $paginator->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data  validated request (role slug as 'role', plain password)
     */
    public function createStaffUser(array $data, User $actor): User
    {
        $role = $this->resolveStaffRole((string) $data['role']);
        $password = (string) $data['password'];
        $branchId = $role->name === Role::ADMIN && filled($data['branch_id'] ?? null)
            ? (int) $data['branch_id']
            : null;

        $user = $this->repository->create([
            'full_name'       => $data['full_name'],
            'father_name'     => $data['father_name'] ?? null,
            'email'           => $data['email'],
            'password'        => Hash::make($password),
            'role_id'         => $role->id,
            'branch_id'       => $branchId,
            'gender'          => $data['gender'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'address'         => $data['address'] ?? null,
            'phone_number'    => $data['phone_number'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'status'          => $data['status'],
            'created_by'      => $actor->id,
            'updated_by'      => $actor->id,
        ]);

        $user->load('role');
        $profileRoute = $user->staffProfileRouteName();
        // Relative path avoids host mismatch (e.g. APP_URL vs how the user opens the app) in notification follow checks.
        $profileUrl = $profileRoute !== null ? route($profileRoute, [], false) : '/';

        $this->notifications->createNotification(
            (int) $user->id,
            'Staff Account Created',
            'Your account has been created.',
            UserNotification::TYPE_STAFF_ACCOUNT_CREATED,
            'staff',
            null,
            $profileUrl,
        );

        return $user->fresh(['role', 'branch']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStaffUser(User $user, array $data, User $actor): User
    {
        $this->assertStaffUser($user);

        $role = $this->resolveStaffRole((string) $data['role']);

        $branchId = $role->name === Role::ADMIN && filled($data['branch_id'] ?? null)
            ? (int) $data['branch_id']
            : null;

        $payload = [
            'full_name'       => $data['full_name'],
            'father_name'     => $data['father_name'] ?? null,
            'email'           => $data['email'],
            'gender'          => $data['gender'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'address'         => $data['address'] ?? null,
            'phone_number'    => $data['phone_number'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'status'          => $data['status'],
            'role_id'         => $role->id,
            'branch_id'       => $branchId,
            'updated_by'      => $actor->id,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make((string) $data['password']);
        }

        return $this->repository->update($user, $payload)->load(['role', 'branch']);
    }

    public function toggleUserStatus(User $user, User $actor): User
    {
        $this->assertStaffUser($user);

        if ((int) $user->id === (int) $actor->id && $user->status === 'active') {
            abort(403, 'You cannot deactivate your own account.');
        }

        $next = $user->status === 'active' ? 'inactive' : 'active';

        return $this->repository->update($user, [
            'status'     => $next,
            'updated_by' => $actor->id,
        ]);
    }

    public function deleteStaffUser(User $user, User $actor): void
    {
        $this->assertStaffUser($user);

        if ((int) $user->id === (int) $actor->id) {
            abort(403, 'You cannot delete your own account.');
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $this->repository->delete($user);
        });
    }

    public function ensureStaffUser(User $user): void
    {
        abort_unless($this->repository->isStaffUser($user), 404);
    }

    private function assertStaffUser(User $user): void
    {
        abort_unless($this->repository->isStaffUser($user), 404);
    }

    private function resolveStaffRole(string $slug): Role
    {
        $slug = strtolower(trim($slug));
        if (! in_array($slug, [Role::ADMIN, Role::FINANCE, Role::APPROVAL_DISCOUNT], true)) {
            throw new InvalidArgumentException('Invalid staff role.');
        }

        return Role::query()->where('name', $slug)->firstOrFail();
    }
}
