<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentApprovedNotification extends Notification
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
            'type'          => 'enrollment_approved',
            'title'         => 'Enrollment Approved',
            'message'       => 'Your enrollment has been approved. You can now view your fee details and payment history.',
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}
