<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Speech Therapy',
            'Remedial Therapy',
            'Occupational Therapy',
            'Physiotherapy',
            'Behavioral Therapy',
            'School Readiness Program',
            'Quran Teaching',
            'Parental Counselling',
            'Group Therapy',
            'Pediatric Clinic',
            'Clinical Assessment',
            'Vocational Training',
        ];

        foreach ($services as $name) {
            Service::updateOrCreate(
                ['name' => $name],
                ['name' => $name, 'status' => 'publish']
            );
        }
    }
}
