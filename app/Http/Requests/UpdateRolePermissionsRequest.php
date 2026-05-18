<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->isSuperAdmin() || $user?->hasPermission('manage_roles'));
    }

    public function rules(): array
    {
        return [
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function permissionIds(): array
    {
        $ids = $this->input('permissions', []);

        return array_values(array_map('intval', is_array($ids) ? $ids : []));
    }

    public function role(): Role
    {
        $role = $this->route('role');

        if (! $role instanceof Role) {
            abort(404);
        }

        return $role;
    }
}
