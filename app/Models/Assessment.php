<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'day',
        'time',
        'branch_id',
        'therapist_id',
        'status',
        'assessment_notes',
        'completed_by',
        'completed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_previous_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'assessment_services');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assessment_children', 'assessment_id', 'child_id');
    }

    public function assessmentNotes(): HasMany
    {
        return $this->hasMany(AssessmentNote::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())->where('status', 'publish');
    }

    /**
     * Cancelled assessments that were published (or legacy cancel with null snapshot) remain visible to child/therapist.
     * Draft-then-cancelled records are staff-only.
     */
    public function isVisibleAsCancelledToAssignees(): bool
    {
        if ($this->status !== 'cancelled') {
            return true;
        }

        return $this->cancelled_previous_status === null
            || $this->cancelled_previous_status === 'publish';
    }

    public function isDraftThenCancelled(): bool
    {
        return $this->status === 'cancelled' && $this->cancelled_previous_status === 'draft';
    }
}
