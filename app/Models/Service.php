<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'created_by', 'updated_by'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'assessment_services');
    }

    /** Therapist users linked via therapist_services (user.id = pivot.therapist_id). */
    public function therapists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'therapist_services', 'service_id', 'therapist_id')
            ->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }
}
