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
}
