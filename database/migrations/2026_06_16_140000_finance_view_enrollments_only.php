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

        $viewEnrollments = Permission::query()->where('name', 'view_enrollments')->first();
        $manageEnrollments = Permission::query()->where('name', 'manage_enrollments')->first();

        if ($manageEnrollments !== null) {
            $finance->permissions()->detach($manageEnrollments->id);
        }

        if ($viewEnrollments !== null) {
            $finance->permissions()->syncWithoutDetaching([$viewEnrollments->id]);
        }
    }

    public function down(): void
    {
        $finance = Role::query()->where('name', Role::FINANCE)->first();
        if ($finance === null) {
            return;
        }

        $viewEnrollments = Permission::query()->where('name', 'view_enrollments')->first();
        $manageEnrollments = Permission::query()->where('name', 'manage_enrollments')->first();

        if ($viewEnrollments !== null) {
            $finance->permissions()->detach($viewEnrollments->id);
        }

        if ($manageEnrollments !== null) {
            $finance->permissions()->syncWithoutDetaching([$manageEnrollments->id]);
        }
    }
};
