<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification
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
            'type'       => 'payment_approved',
            'title'      => 'Payment Verified',
            'message'    => 'Your payment of PKR ' . number_format($this->payment->amount, 2) . ' has been verified.'
                . (filled($this->payment->receipt_number) ? ' Receipt: ' . $this->payment->receipt_number : ''),
            'payment_id' => $this->payment->id,
        ];
    }
}
