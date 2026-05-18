<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChildRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $child,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'child_rejected',
            'title'   => 'Account Rejected',
            'message' => "Your account registration has been rejected. Reason: {$this->reason}",
        ];
    }
}
