<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TherapistProfile extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'cnic_number',
        'qualification',
        'specialization',
        'working_days',
        'available_time_slots',
        'break_time',
        'documents',
        'status',
    ];

    protected $casts = [
        'working_days'        => 'array',
        'available_time_slots' => 'array',
        'documents'           => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'therapist_id', 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Break window for display, same 12-hour style as generated slots (g:iA e.g. 12:00PM - 1:00PM).
     */
    /**
     * Slots for UI: supports legacy string entries and current array shape.
     *
     * @return array<int, array{slot: string, disabled: bool}>
     */
    public function normalizedAvailableSlotsForDisplay(): array
    {
        return collect($this->available_time_slots ?? [])
            ->map(function ($slot): array {
                if (is_string($slot)) {
                    return ['slot' => $slot, 'disabled' => false];
                }

                if (is_array($slot)) {
                    return [
                        'slot'     => (string) ($slot['slot'] ?? ''),
                        'disabled' => filter_var($slot['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                }

                return ['slot' => '', 'disabled' => false];
            })
            ->filter(static fn (array $slot): bool => $slot['slot'] !== '')
            ->values()
            ->all();
    }

    public function formattedBreakTimeLabel(): ?string
    {
        if (empty($this->break_time)) {
            return null;
        }

        $parts = preg_split('/\s*-\s*/', trim((string) $this->break_time), 2);
        if (count($parts) !== 2) {
            return $this->break_time;
        }

        try {
            $start = Carbon::parse(trim($parts[0]))->format('g:iA');
            $end   = Carbon::parse(trim($parts[1]))->format('g:iA');

            return $start.' - '.$end;
        } catch (\Throwable) {
            return $this->break_time;
        }
    }

    /**
     * Session window for edit forms, derived from stored available_time_slots.
     *
     * @return array{start: string, end: string} 24h H:i
     */
    public function inferredSlotBounds(): array
    {
        $defaults = ['start' => '09:00', 'end' => '17:00'];
        $slots = $this->available_time_slots ?? [];

        if ($slots === []) {
            return $defaults;
        }

        $startMinutes = null;
        $endMinutes = null;

        foreach ($slots as $slot) {
            if (is_array($slot) && ! empty($slot['start']) && ! empty($slot['end'])) {
                $sMin = self::timeToMinutes((string) $slot['start']);
                $eMin = self::timeToMinutes((string) $slot['end']);
            } elseif (is_array($slot) && ! empty($slot['slot'])) {
                [$sMin, $eMin] = self::parseSlotLabelToMinutes((string) $slot['slot']);
            } elseif (is_string($slot) && $slot !== '') {
                [$sMin, $eMin] = self::parseSlotLabelToMinutes($slot);
            } else {
                continue;
            }

            if ($sMin === null || $eMin === null) {
                continue;
            }

            $startMinutes = $startMinutes === null ? $sMin : min($startMinutes, $sMin);
            $endMinutes = $endMinutes === null ? $eMin : max($endMinutes, $eMin);
        }

        if ($startMinutes === null || $endMinutes === null) {
            return $defaults;
        }

        return [
            'start' => self::minutesToTime($startMinutes),
            'end'   => self::minutesToTime($endMinutes),
        ];
    }

    private static function timeToMinutes(string $time): ?int
    {
        try {
            $parsed = Carbon::createFromFormat('H:i', $time);

            return $parsed->hour * 60 + $parsed->minute;
        } catch (\Throwable) {
            try {
                $parsed = Carbon::parse($time);

                return $parsed->hour * 60 + $parsed->minute;
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private static function minutesToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60) % 24;
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function parseSlotLabelToMinutes(string $label): array
    {
        $parts = preg_split('/\s*-\s*/', trim($label), 2);
        if (count($parts) !== 2) {
            return [null, null];
        }

        try {
            $start = Carbon::parse(trim($parts[0]));
            $end = Carbon::parse(trim($parts[1]));

            return [$start->hour * 60 + $start->minute, $end->hour * 60 + $end->minute];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
