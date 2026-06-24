<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public const MANAGE_USERS           = 'manage_users';
    public const MANAGE_ROLES           = 'manage_roles';
    public const MANAGE_CHILDREN        = 'manage_children';
    public const REGISTER_CHILDREN      = 'register_children';
    public const VIEW_CHILDREN          = 'view_children';
    public const APPROVE_CHILDREN       = 'approve_children';
    public const MANAGE_DISABILITIES    = 'manage_disabilities';
    public const MANAGE_SERVICES        = 'manage_services';
    public const MANAGE_BRANCHES        = 'manage_branches';
    public const MANAGE_THERAPISTS      = 'manage_therapists';
    public const MANAGE_ASSESSMENTS     = 'manage_assessments';
    public const MANAGE_ENROLLMENTS     = 'manage_enrollments';
    public const VIEW_ENROLLMENTS       = 'view_enrollments';
    public const APPROVE_HIGH_DISCOUNT  = 'approve_high_discount';
    public const VIEW_STAFF_USERS       = 'view_staff_users';
    public const MANAGE_PAYMENTS        = 'manage_payments';
    public const VERIFY_PAYMENTS        = 'verify_payments';
    public const VIEW_FINANCE_REPORTS   = 'view_finance_reports';
    public const VIEW_ASSIGNED_CHILDREN = 'view_assigned_children';
    public const VIEW_CHILD_DASHBOARD   = 'view_child_dashboard';
    public const MANAGE_SETTINGS        = 'manage_settings';

    protected $fillable = ['name', 'display_name', 'module'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
