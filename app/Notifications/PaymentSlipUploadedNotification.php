<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentSlipUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'payment_slip_uploaded',
            'title'      => 'Payment Slip Uploaded',
            'message'    => "{$this->payment->child->full_name} has uploaded a payment slip of PKR " . number_format($this->payment->amount, 2) . " pending verification.",
            'payment_id' => $this->payment->id,
        ];
    }
}
