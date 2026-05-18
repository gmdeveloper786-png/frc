<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'father_name',
        'email',
        'password',
        'role_id',
        'age',
        'gender',
        'date_of_birth',
        'address',
        'phone_number',
        'whatsapp_number',
        'parent_notes',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at'       => 'datetime',
        'rejected_at'       => 'datetime',
        'date_of_birth'     => 'date',
        'password'          => 'hashed',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function therapistProfile(): HasOne
    {
        return $this->hasOne(TherapistProfile::class);
    }

    /** Published services this therapist delivers (pivot: therapist_services). */
    public function therapistServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'therapist_services', 'therapist_id', 'service_id')
            ->withTimestamps();
    }

    public function disabilities(): BelongsToMany
    {
        return $this->belongsToMany(Disability::class, 'child_disabilities');
    }

    /** Assessments where this user is an assigned child (pivot child_id). */
    public function childAssessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'assessment_children', 'child_id', 'assessment_id');
    }

    /** @deprecated Use childAssessments(); alias kept for backward compatibility. */
    public function assessments(): BelongsToMany
    {
        return $this->childAssessments();
    }

    public function assignedTherapistAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'therapist_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'child_id');
    }

    /** Enrollment that counts toward allowing child account status Active. */
    public function hasOperationalEnrollment(): bool
    {
        return $this->enrollments()
            ->whereIn('status', ['approved', 'active', 'completed'])
            ->exists();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'child_id');
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    /** Progress / session documentation authored by this therapist. */
    public function therapistProgressNotes(): HasMany
    {
        return $this->hasMany(ProgressNote::class, 'therapist_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** In-app inbox (user_notifications); not Laravel database notifications. */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    // ─── Role helpers ─────────────────────────────────────────────────────────

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->name, $roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isTherapist(): bool
    {
        return $this->hasRole(Role::THERAPIST);
    }

    public function isFinance(): bool
    {
        return $this->hasRole(Role::FINANCE);
    }

    public function isChild(): bool
    {
        return $this->hasRole(Role::CHILD);
    }

    /** Route name for this user's main dashboard (after login / safe redirects). */
    public function dashboardRouteName(): ?string
    {
        return match ($this->role?->name) {
            Role::SUPER_ADMIN => 'dashboard.super-admin',
            Role::ADMIN        => 'dashboard.admin',
            Role::THERAPIST   => 'dashboard.therapist',
            Role::FINANCE     => 'dashboard.finance',
            Role::CHILD       => 'dashboard.child',
            default           => null,
        };
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'active'], true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeChildren($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', Role::CHILD));
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', $role));
    }
}
