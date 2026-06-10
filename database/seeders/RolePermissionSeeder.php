<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Default role ↔ permission mapping for fresh installs only.
     * On production, use Super Admin → Roles & Permissions instead of re-running this seeder.
     */
    public function run(): void
    {
        $allPermissions = Permission::pluck('id')->toArray();

        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin      = Role::where('name', 'admin')->first();
        $therapist  = Role::where('name', 'therapist')->first();
        $finance           = Role::where('name', 'finance')->first();
        $approvalDiscount  = Role::where('name', 'approval_discount')->first();
        $child             = Role::where('name', 'child')->first();

        // Super Admin gets all permissions (includes manage_settings)
        $superAdmin->permissions()->sync($allPermissions);

        // Admin permissions
        $adminPermissions = Permission::whereIn('name', [
            'manage_children',
            'approve_children',
            'manage_therapists',
            'manage_assessments',
            'manage_enrollments',
            'manage_payments',
            'verify_payments',
        ])->pluck('id')->toArray();
        $admin->permissions()->sync($adminPermissions);

        // Finance permissions
        $financePermissions = Permission::whereIn('name', [
            'manage_children',
            'manage_enrollments',
            'manage_payments',
            'verify_payments',
            'view_finance_reports',
        ])->pluck('id')->toArray();
        $finance->permissions()->sync($financePermissions);

        // Approval Discount permissions
        $approvalDiscountPermissions = Permission::whereIn('name', [
            'approve_high_discount',
            'view_children',
            'view_enrollments',
        ])->pluck('id')->toArray();
        $approvalDiscount?->permissions()->sync($approvalDiscountPermissions);

        // Therapist permissions
        $therapistPermissions = Permission::whereIn('name', [
            'view_assigned_children',
        ])->pluck('id')->toArray();
        $therapist->permissions()->sync($therapistPermissions);

        // Child permissions
        $childPermissions = Permission::whereIn('name', [
            'view_child_dashboard',
        ])->pluck('id')->toArray();
        $child->permissions()->sync($childPermissions);
    }
}
