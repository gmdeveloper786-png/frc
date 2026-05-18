<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function __construct(private readonly RolePermissionService $rolePermissions) {}

    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        $roles = $this->rolePermissions->listRoles();

        return view('super-admin.roles.index', compact('roles'));
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorizeAccess($request);

        $role = $this->rolePermissions->loadRolePermissions($role);
        $editable = $this->rolePermissions->isEditable($role);

        $permissionsByModule = $editable
            ? $this->rolePermissions->permissionsGroupedByModule()
            : $this->rolePermissions->allPermissionsGroupedByModule();

        $assignedIds = $role->permissions->pluck('id')->all();

        return view('super-admin.roles.edit', compact('role', 'permissionsByModule', 'assignedIds', 'editable'));
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        if (! $this->rolePermissions->isEditable($role)) {
            return redirect()
                ->route('super-admin.roles.edit', $role)
                ->with('error', 'Super Admin always has all permissions and cannot be modified.');
        }

        $this->rolePermissions->syncRolePermissions($role, $request->permissionIds());

        return redirect()
            ->route('super-admin.roles.index')
            ->with('success', "Permissions updated for {$role->display_name}.");
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user?->isSuperAdmin() || $user?->hasPermission('manage_roles'),
            403
        );
    }
}
