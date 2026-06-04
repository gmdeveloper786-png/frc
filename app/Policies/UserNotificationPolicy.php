<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;
use App\Support\StaffBranchScope;

class UserNotificationPolicy
{
    public function view(User $user, UserNotification $userNotification): bool
    {
        return (int) $userNotification->user_id === (int) $user->id
            && StaffBranchScope::notificationVisibleToStaff($userNotification, $user);
    }

    public function update(User $user, UserNotification $userNotification): bool
    {
        return $this->view($user, $userNotification);
    }

    public function delete(User $user, UserNotification $userNotification): bool
    {
        return $this->view($user, $userNotification);
    }
}
