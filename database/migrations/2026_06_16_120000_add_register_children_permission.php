<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::query()->updateOrCreate(
            ['name' => 'register_children'],
            [
                'display_name' => 'Register Child',
                'module'       => 'children',
            ],
        );

        $admin = Role::query()->where('name', Role::ADMIN)->first();
        if ($admin !== null) {
            $admin->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $permission = Permission::query()->where('name', 'register_children')->first();
        if ($permission === null) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
