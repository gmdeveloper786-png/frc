<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'city', 'address', 'phone', 'status', 'created_by', 'updated_by'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function therapistProfiles(): HasMany
    {
        return $this->hasMany(TherapistProfile::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Select label: "Branch Name — City" (matches registration branch picker). */
    public function displayLabel(): string
    {
        $name = trim((string) $this->name);
        $city = trim((string) ($this->city ?? ''));

        return $city !== '' ? "{$name} — {$city}" : $name;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    /** Columns required for select dropdowns ({@see displayLabel()} needs city). */
    public function scopeForDropdown(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'city']);
    }

    /**
     * Dropdown order: cities with more published branches first, then city name, then branch name.
     * e.g. all 3 Karachi branches together at the top when Karachi has the most branches.
     */
    public function scopeOrderedForDropdown(Builder $query): Builder
    {
        $table = DB::connection()->getQueryGrammar()->wrapTable($query->getModel()->getTable());

        return $query
            ->orderByRaw("(
                SELECT COUNT(*)
                FROM {$table} AS city_group
                WHERE city_group.deleted_at IS NULL
                  AND city_group.status = 'publish'
                  AND COALESCE(city_group.city, '') = COALESCE({$table}.city, '')
            ) DESC")
            ->orderByRaw("COALESCE({$table}.city, '') ASC")
            ->orderBy($query->qualifyColumn('name'));
    }

    /** Branch admins see only their assigned branch; super admin sees all. */
    public function scopeForStaff($query, User $staff)
    {
        if ($staff->isSuperAdmin()) {
            return $query;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            return $query->whereKey($staff->branch_id);
        }

        if ($staff->isAdmin()) {
            return $query->whereRaw('0 = 1');
        }

        return $query;
    }
}
