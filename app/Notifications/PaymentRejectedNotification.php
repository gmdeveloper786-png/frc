<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification
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
            'type'       => 'payment_rejected',
            'title'      => 'Payment Rejected',
            'message'    => 'Your payment of ' . frc_pkr($this->payment->amount) . ' was rejected. Reason: ' . $this->payment->rejection_reason,
            'payment_id' => $this->payment->id,
        ];
    }
}
