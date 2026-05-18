<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressNote extends Model
{
    use SoftDeletes;

    public const PROGRESS_LEVELS = ['excellent', 'good', 'average', 'needs_improvement', 'no_response'];

    public const STATUSES = ['draft', 'completed'];

    protected $fillable = [
        'child_id',
        'therapist_id',
        'enrollment_id',
        'enrollment_schedule_id',
        'service_id',
        'session_date',
        'therapy_goal',
        'notes',
        'child_response',
        'progress_level',
        'parent_instructions',
        'next_plan',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function enrollmentSchedule(): BelongsTo
    {
        return $this->belongsTo(EnrollmentSchedule::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function labelForProgressLevel(?string $level): string
    {
        return match ($level) {
            'excellent' => 'Excellent',
            'good' => 'Good',
            'average' => 'Average',
            'needs_improvement' => 'Needs Improvement',
            'no_response' => 'No Response',
            default => $level ? ucfirst(str_replace('_', ' ', $level)) : '—',
        };
    }
}
