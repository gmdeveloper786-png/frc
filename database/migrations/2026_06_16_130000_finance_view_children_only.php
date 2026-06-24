<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $finance = Role::query()->where('name', Role::FINANCE)->first();
        if ($finance === null) {
            return;
        }

        $viewChildren = Permission::query()->where('name', 'view_children')->first();
        $manageChildren = Permission::query()->where('name', 'manage_children')->first();

        if ($manageChildren !== null) {
            $finance->permissions()->detach($manageChildren->id);
        }

        if ($viewChildren !== null) {
            $finance->permissions()->syncWithoutDetaching([$viewChildren->id]);
        }
    }

    public function down(): void
    {
        $finance = Role::query()->where('name', Role::FINANCE)->first();
        if ($finance === null) {
            return;
        }

        $viewChildren = Permission::query()->where('name', 'view_children')->first();
        $manageChildren = Permission::query()->where('name', 'manage_children')->first();

        if ($viewChildren !== null) {
            $finance->permissions()->detach($viewChildren->id);
        }

        if ($manageChildren !== null) {
            $finance->permissions()->syncWithoutDetaching([$manageChildren->id]);
        }
    }
};
