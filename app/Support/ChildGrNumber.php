<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Auto-incrementing GR numbers for child accounts (FRC-CH-000001, FRC-CH-000002, …). */
final class ChildGrNumber
{
    public const PREFIX = 'FRC-CH-';

    /** Zero-padded sequence length after prefix (FRC-CH-000001). */
    public const SEQUENCE_LENGTH = 6;

    public static function next(): string
    {
        return DB::transaction(function (): string {
            $maxSeq = 0;

            $existing = User::query()
                ->whereHas('role', fn ($q) => $q->where('name', Role::CHILD))
                ->whereNotNull('gr_number')
                ->where('gr_number', 'like', self::PREFIX . '%')
                ->lockForUpdate()
                ->pluck('gr_number');

            foreach ($existing as $gr) {
                if (preg_match('/^' . preg_quote(self::PREFIX, '/') . '(\d+)$/', (string) $gr, $matches)) {
                    $maxSeq = max($maxSeq, (int) $matches[1]);
                }
            }

            return self::PREFIX . str_pad((string) ($maxSeq + 1), self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
        });
    }
}
