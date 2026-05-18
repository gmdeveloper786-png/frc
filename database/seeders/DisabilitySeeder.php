<?php

namespace Database\Seeders;

use App\Models\Disability;
use Illuminate\Database\Seeder;

class DisabilitySeeder extends Seeder
{
    public function run(): void
    {
        $disabilities = [
            'Thalassemia',
            'Blindness',
            'Hearing problem',
            'Club foot',
            'Physical disability',
            'Down syndrome',
            'Cerebral palsy',
            'ADHD',
            'Anxiety disorder',
            'Bipolar disorder',
            'Speech disorder',
            'Intellectual disability',
            'Deaf',
            'Epilepsy',
            'Other',
        ];

        foreach ($disabilities as $name) {
            Disability::updateOrCreate(
                ['name' => $name],
                ['name' => $name, 'status' => 'publish']
            );
        }
    }
}
