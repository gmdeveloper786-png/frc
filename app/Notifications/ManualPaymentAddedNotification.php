<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ManualPaymentAddedNotification extends Notification
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
            'type'       => 'manual_payment_added',
            'title'      => 'Payment Received',
            'message'    => 'A payment of ' . frc_pkr($this->payment->amount) . ' has been recorded.'
                . (filled($this->payment->receipt_number) ? ' Receipt: ' . $this->payment->receipt_number : ''),
            'payment_id' => $this->payment->id,
        ];
    }
}
