<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin'],
            ['name' => 'admin',       'display_name' => 'Admin'],
            ['name' => 'therapist',   'display_name' => 'Therapist'],
            ['name' => 'finance',     'display_name' => 'Finance'],
            ['name' => 'child',       'display_name' => 'Child'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
