<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'manage_users',           'display_name' => 'Manage Users',           'module' => 'users'],
            ['name' => 'manage_roles',            'display_name' => 'Manage Roles',           'module' => 'users'],
            ['name' => 'manage_children',         'display_name' => 'Manage Children',        'module' => 'children'],
            ['name' => 'approve_children',        'display_name' => 'Approve Children',       'module' => 'children'],
            ['name' => 'manage_disabilities',     'display_name' => 'Manage Disabilities',    'module' => 'disabilities'],
            ['name' => 'manage_services',         'display_name' => 'Manage Services',        'module' => 'services'],
            ['name' => 'manage_branches',         'display_name' => 'Manage Branches',        'module' => 'branches'],
            ['name' => 'manage_therapists',       'display_name' => 'Manage Therapists',      'module' => 'therapists'],
            ['name' => 'manage_assessments',      'display_name' => 'Manage Assessments',     'module' => 'assessments'],
            ['name' => 'manage_enrollments',      'display_name' => 'Manage Enrollments',     'module' => 'enrollments'],
            ['name' => 'approve_high_discount',   'display_name' => 'Approve High Discount',  'module' => 'enrollments'],
            ['name' => 'manage_payments',         'display_name' => 'Manage Payments',        'module' => 'payments'],
            ['name' => 'verify_payments',         'display_name' => 'Verify Payments',        'module' => 'payments'],
            ['name' => 'view_finance_reports',    'display_name' => 'View Finance Reports',   'module' => 'reports'],
            ['name' => 'view_assigned_children',  'display_name' => 'View Assigned Children', 'module' => 'therapists'],
            ['name' => 'view_child_dashboard',    'display_name' => 'View Child Dashboard',   'module' => 'children'],
            ['name' => 'manage_settings',         'display_name' => 'Manage Settings',        'module' => 'system'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
