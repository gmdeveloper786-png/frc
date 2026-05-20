<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->uuid('enrollment_group_id')->nullable()->after('child_id');
            $table->index('enrollment_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['enrollment_group_id']);
            $table->dropColumn('enrollment_group_id');
        });
    }
};
