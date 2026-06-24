<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'manage_disabilities')
            ->update([
                'display_name' => 'Manage Present Complaints',
                'module'       => 'present_complaints',
            ]);

        DB::table('disabilities')
            ->where('name', 'Physical disability')
            ->update(['name' => 'Physical limitation']);

        DB::table('disabilities')
            ->where('name', 'Intellectual disability')
            ->update(['name' => 'Intellectual limitation']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'manage_disabilities')
            ->update([
                'display_name' => 'Manage Disabilities',
                'module'       => 'disabilities',
            ]);

        DB::table('disabilities')
            ->where('name', 'Physical limitation')
            ->update(['name' => 'Physical disability']);

        DB::table('disabilities')
            ->where('name', 'Intellectual limitation')
            ->update(['name' => 'Intellectual disability']);
    }
};
