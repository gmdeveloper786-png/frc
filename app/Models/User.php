<?php

namespace App\Models;

use App\Support\ChildGrNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'father_name',
        'email',
        'gr_number',
        'password',
        'role_id',
        'branch_id',
        'age',
        'gender',
        'date_of_birth',
        'address',
        'phone_number',
        'whatsapp_number',
        'parent_notes',
        'documents',
        'other_disability',
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
        'documents'         => 'array',
        'password'          => 'hashed',
    ];

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->isDirty('password')) {
                $user->remember_token = Str::random(60);
            }
        });

        static::creating(function (User $user): void {
            if (filled($user->gr_number)) {
                return;
            }

            $childRoleId = Role::query()->where('name', Role::CHILD)->value('id');
            if ($childRoleId !== null && (int) $user->role_id === (int) $childRoleId) {
                $user->gr_number = ChildGrNumber::next();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Branch assignment for branch-scoped admin accounts. */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    /** When "Other" is selected, show the custom description instead of the generic label. */
    public function disabilityLabel(Disability $disability): string
    {
        if (strcasecmp((string) $disability->name, 'Other') === 0) {
            $custom = trim((string) ($this->other_disability ?? ''));
            if ($custom !== '') {
                return $custom;
            }
        }

        return (string) $disability->name;
    }

    /** @return array{id: int, full_name: string, gr_number: ?string, age: ?int, phone_number: ?string, present_complaints: list<string>} */
    public function toApprovedPickerArray(): array
    {
        $this->loadMissing('disabilities');

        return [
            'id'                 => (int) $this->id,
            'full_name'          => (string) $this->full_name,
            'gr_number'          => $this->gr_number,
            'age'                => $this->age,
            'phone_number'       => $this->phone_number,
            'present_complaints' => $this->disabilities
                ->map(fn (Disability $disability): string => $this->disabilityLabel($disability))
                ->values()
                ->all(),
        ];
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

    public function isApprovalDiscount(): bool
    {
        return $this->hasRole(Role::APPROVAL_DISCOUNT);
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
            Role::FINANCE           => 'dashboard.finance',
            Role::APPROVAL_DISCOUNT => 'enrollments.high-discount',
            Role::CHILD             => 'dashboard.child',
            default           => null,
        };
    }

    /** Self-service profile page for admin/finance staff accounts. */
    public function staffProfileRouteName(): ?string
    {
        return match ($this->role?->name) {
            Role::ADMIN   => 'admin.profile',
            Role::FINANCE => 'finance.profile',
            default       => null,
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

    /** Whether this staff user may approve/reject a child for their registered branch. */
    public function canApproveChild(User $child): bool
    {
        if (! $this->hasPermission('approve_children')) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isAdmin() && $child->branch_id !== null && (int) $this->branch_id === (int) $child->branch_id) {
            return true;
        }

        return false;
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

    /** Limit child lists to the branch assigned to a branch admin; super admin sees all. */
    public function scopeVisibleToStaff($query, User $staff)
    {
        if ($staff->isSuperAdmin()) {
            return $query;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            return $query->where('branch_id', $staff->branch_id);
        }

        if ($staff->isAdmin()) {
            return $query->whereRaw('0 = 1');
        }

        return $query;
    }
}
