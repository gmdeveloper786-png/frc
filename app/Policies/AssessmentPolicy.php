<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->hasPermission('manage_assessments')) {
            return true;
        }

        if ($user->isTherapist() && (int) $assessment->therapist_id === (int) $user->id) {
            return $this->visibleToAssignee($assessment);
        }

        if ($user->isChild() && $assessment->children()->where('users.id', $user->id)->exists()) {
            return $this->visibleToAssignee($assessment);
        }

        return false;
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->hasPermission('manage_assessments');
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->hasPermission('manage_assessments');
    }

    public function complete(User $user, Assessment $assessment): bool
    {
        if ($user->hasPermission('manage_assessments')) {
            return true;
        }

        return $user->isTherapist() && (int) $assessment->therapist_id === (int) $user->id;
    }

    public function cancel(User $user, Assessment $assessment): bool
    {
        return $user->isSuperAdmin() && $user->hasPermission('manage_assessments');
    }

    private function visibleToAssignee(Assessment $assessment): bool
    {
        if ($assessment->status === 'draft') {
            return false;
        }

        if ($assessment->status === 'cancelled' && ! $assessment->isVisibleAsCancelledToAssignees()) {
            return false;
        }

        return true;
    }
}
