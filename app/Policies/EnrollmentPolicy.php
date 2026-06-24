<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->role?->name === 'child') {
            return (int) $enrollment->child_id === (int) $user->id
                && $enrollment->isVisibleToChild();
        }

        return $user->hasPermission('manage_enrollments')
            || $user->hasPermission('view_enrollments')
            || $user->hasPermission('view_finance_reports');
    }

    public function create(User $user): bool
    {
        if ($user->isFinance() || $user->isApprovalDiscount()) {
            return false;
        }

        return $user->hasPermission('manage_enrollments');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        if ($user->isFinance() || $user->isApprovalDiscount()) {
            return false;
        }

        return $user->hasPermission('manage_enrollments');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        if ($user->isFinance() || $user->isApprovalDiscount()) {
            return false;
        }

        return $user->hasPermission('manage_enrollments');
    }

    public function approve(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->status === 'pending_super_admin_approval') {
            return $user->hasPermission('approve_high_discount');
        }

        return $user->hasPermission('manage_enrollments');
    }

    public function reject(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->status === 'pending_super_admin_approval') {
            return $user->hasPermission('approve_high_discount');
        }

        return $user->hasPermission('manage_enrollments');
    }

    /**
     * Full dated session grid for one enrolment ({@see \App\Http\Controllers\Web\EnrollmentController::fullSchedule}).
     */
    public function viewFullSchedule(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasPermission('manage_enrollments')
            || $user->hasPermission('view_enrollments')
            || $user->hasPermission('view_finance_reports')
            || $user->hasPermission('manage_payments')) {
            return true;
        }

        if ($user->role?->name === 'child') {
            return $user->isApproved()
                && (int) $enrollment->child_id === (int) $user->id
                && $enrollment->isVisibleToChild();
        }

        if ($user->role?->name === 'therapist') {
            return $this->therapistAssignedToEnrollment($user, $enrollment);
        }

        return false;
    }

    private function therapistAssignedToEnrollment(User $user, Enrollment $enrollment): bool
    {
        if ((int) ($enrollment->therapist_id ?? 0) === (int) $user->id) {
            return true;
        }

        return $enrollment->schedules()->where('therapist_id', $user->id)->exists();
    }
}
