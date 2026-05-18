<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Enrollment $enrollment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'enrollment_created',
            'title'         => 'Enrollment Created',
            'message'       => 'An enrollment has been created for you. It is currently under review.',
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}
