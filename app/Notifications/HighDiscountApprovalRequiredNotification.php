<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HighDiscountApprovalRequiredNotification extends Notification
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
            'type'          => 'high_discount_approval_required',
            'title'         => 'High Discount Approval Required',
            'message'       => "Enrollment for {$this->enrollment->child->full_name} requires your approval (discount: {$this->enrollment->discount_percentage}%).",
            'enrollment_id' => $this->enrollment->id,
        ];
    }
}
