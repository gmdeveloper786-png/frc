<?php

use App\Models\Role;
use App\Models\User;
use App\Support\ChildGrNumber;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $childRoleId = Role::query()->where('name', Role::CHILD)->value('id');
        if ($childRoleId === null) {
            return;
        }

        User::query()
            ->where('role_id', $childRoleId)
            ->whereNotNull('gr_number')
            ->where('gr_number', 'like', ChildGrNumber::PREFIX . '%')
            ->orderBy('id')
            ->each(function (User $user): void {
                if (! preg_match('/^' . preg_quote(ChildGrNumber::PREFIX, '/') . '(\d+)$/', (string) $user->gr_number, $matches)) {
                    return;
                }

                $normalized = ChildGrNumber::PREFIX . str_pad(
                    (string) (int) $matches[1],
                    ChildGrNumber::SEQUENCE_LENGTH,
                    '0',
                    STR_PAD_LEFT,
                );

                if ($normalized !== $user->gr_number) {
                    $user->update(['gr_number' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // No rollback — previous 4-digit formatting is not restored.
    }
};
