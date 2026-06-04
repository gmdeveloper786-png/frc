<?php

namespace App\Services;

use App\Models\Payment;

class ReceiptService
{
    public function getReceiptData(Payment $payment): array
    {
        $payment->load(['child', 'enrollment.branch', 'enrollment.service', 'enrollment.therapist', 'receivedBy', 'verifiedBy']);

        return [
            'receipt_number'    => $payment->receipt_number,
            'payment_date'      => $payment->payment_date->format('d M Y'),
            'verified_at'       => $payment->verified_at?->format('d M Y h:i A'),
            'verified_by'       => $payment->verifiedBy?->full_name,
            'child_name'        => $payment->child->full_name,
            'child_gr_number'   => $payment->child->gr_number,
            'child_email'       => $payment->child->email,
            'enrollment_id'     => (int) $payment->enrollment_id,
            'branch'            => $payment->enrollment->branch?->name,
            'therapist'         => $payment->enrollment->therapist?->full_name,
            'service'           => $payment->enrollment->service?->name,
            'amount'            => $payment->amount,
            'payment_method'    => Payment::labelForPaymentMethod($payment->payment_method),
            'transaction_ref'   => $payment->transaction_reference,
            'total_fee'         => $payment->enrollment->final_total,
            'paid_amount'       => $payment->enrollment->paid_amount,
            'remaining_amount'  => $payment->enrollment->remaining_amount,
            'received_by'       => $payment->receivedBy?->full_name,
            'status'            => $payment->status,
            'notes'             => filled($payment->notes) ? trim((string) $payment->notes) : null,
            'from_uploaded_slip' => filled($payment->payment_slip),
        ];
    }
}
