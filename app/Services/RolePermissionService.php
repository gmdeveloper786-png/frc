<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RolePermissionService
{
    /** Permissions reserved for Super Admin only (not assignable via UI). */
    private const NON_ASSIGNABLE = [
        Permission::MANAGE_ROLES,
        Permission::MANAGE_USERS,
    ];

    public function listRoles(): Collection
    {
        return Role::query()
            ->withCount('permissions')
            ->orderBy('display_name')
            ->get();
    }

    public function permissionsGroupedByModule(): Collection
    {
        return Permission::query()
            ->whereNotIn('name', self::NON_ASSIGNABLE)
            ->orderBy('module')
            ->orderBy('display_name')
            ->get()
            ->groupBy(fn (Permission $p) => $p->module ?: 'general');
    }

    public function allPermissionsGroupedByModule(): Collection
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('display_name')
            ->get()
            ->groupBy(fn (Permission $p) => $p->module ?: 'general');
    }

    public function isEditable(Role $role): bool
    {
        return $role->name !== Role::SUPER_ADMIN;
    }

    public function loadRolePermissions(Role $role): Role
    {
        return $role->load('permissions');
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        if (! $this->isEditable($role)) {
            throw new InvalidArgumentException('Super Admin role permissions cannot be changed.');
        }

        $ids = Permission::query()
            ->whereIn('id', $permissionIds)
            ->whereNotIn('name', self::NON_ASSIGNABLE)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($ids);
    }
}
