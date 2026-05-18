<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentScheduleOccurrence extends Model
{
    protected $fillable = [
        'enrollment_schedule_id',
        'occurrence_date',
        'status',
        'session_notes',
        'started_at',
        'started_by',
        'completed_at',
        'completed_by',
        'completion_note',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EnrollmentSchedule::class, 'enrollment_schedule_id');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
