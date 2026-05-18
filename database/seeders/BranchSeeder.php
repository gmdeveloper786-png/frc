<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Shahrah-e-Faisal', 'city' => 'Karachi', 'phone' => '03001234567'],
            ['name' => 'Malir Town',        'city' => 'Karachi', 'phone' => '03001234567'],
            ['name' => 'FB Area Block 6',   'city' => 'Karachi', 'phone' => '03001234567'],
            ['name' => 'Faisalabad',        'city' => 'Faisalabad', 'phone' => '03001234567'],
            ['name' => 'Lahore',            'city' => 'Lahore', 'phone' => '03001234567'],
            ['name' => 'Sialkot',           'city' => 'Sialkot', 'phone' => '03001234567'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['name' => $branch['name']],
                array_merge($branch, ['status' => 'publish'])
            );
        }
    }
}
