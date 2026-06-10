<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewChild(User $staff, User $child): bool
    {
        if (! $child->isChild()) {
            return false;
        }

        if (! $staff->hasPermission('manage_children')
            && ! $staff->hasPermission('view_children')
            && ! $staff->hasPermission('approve_children')) {
            return false;
        }

        return $this->childInStaffScope($staff, $child);
    }

    public function updateChild(User $staff, User $child): bool
    {
        if (! $child->isChild() || ! $staff->hasPermission('manage_children')) {
            return false;
        }

        return $this->childInStaffScope($staff, $child);
    }

    public function deleteChild(User $staff, User $child): bool
    {
        return $this->updateChild($staff, $child);
    }

    public function approveChild(User $staff, User $child): bool
    {
        if (! $child->isChild()) {
            return false;
        }

        return $staff->canApproveChild($child);
    }

    private function childInStaffScope(User $staff, User $child): bool
    {
        if ($staff->isFinance() || $staff->isApprovalDiscount()) {
            return true;
        }

        if ($staff->isAdmin() && $staff->branch_id) {
            return (int) $child->branch_id === (int) $staff->branch_id;
        }

        if ($staff->isAdmin()) {
            return false;
        }

        return true;
    }
}
