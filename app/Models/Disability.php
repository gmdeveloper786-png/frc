<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disability extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'status', 'created_by', 'updated_by'];

    protected $casts = ['status' => 'string'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'child_disabilities');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }
}
