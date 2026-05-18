<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChildApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly User $child) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'child_approved',
            'title'   => 'Account Approved',
            'message' => 'Your account has been approved. You can now login and access your dashboard.',
        ];
    }
}
