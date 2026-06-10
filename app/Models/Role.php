<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const SUPER_ADMIN       = 'super_admin';
    public const ADMIN             = 'admin';
    public const THERAPIST         = 'therapist';
    public const FINANCE           = 'finance';
    public const APPROVAL_DISCOUNT = 'approval_discount';
    public const CHILD             = 'child';

    protected $fillable = ['name', 'display_name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }
}
