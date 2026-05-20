<?php

use App\Models\Role;
use App\Support\ChildGrNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gr_number', 24)->nullable()->unique()->after('email');
        });

        $childRoleId = Role::query()->where('name', Role::CHILD)->value('id');
        if ($childRoleId === null) {
            return;
        }

        $childIds = DB::table('users')
            ->where('role_id', $childRoleId)
            ->whereNull('gr_number')
            ->orderBy('id')
            ->pluck('id');

        foreach ($childIds as $id) {
            DB::table('users')
                ->where('id', $id)
                ->update(['gr_number' => ChildGrNumber::next()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['gr_number']);
            $table->dropColumn('gr_number');
        });
    }
};
