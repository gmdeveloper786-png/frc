<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'full_name'   => 'Super Admin',
                'father_name' => null,
                'email'       => 'superadmin@gmail.com',
                'password'    => Hash::make('12345678'),
                'role_id'     => $superAdminRole->id,
                'status'      => 'active',
            ]
        );
    }
}
