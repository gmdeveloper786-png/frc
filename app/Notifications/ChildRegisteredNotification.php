<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChildRegisteredNotification extends Notification
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
            'type'    => 'child_registered',
            'title'   => 'New Child Registration',
            'message' => "{$this->child->full_name} has registered and is awaiting approval.",
            'child_id' => $this->child->id,
        ];
    }
}
