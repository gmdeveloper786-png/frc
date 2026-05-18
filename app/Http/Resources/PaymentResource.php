<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'receipt_number'       => $this->receipt_number,
            'amount'               => $this->amount,
            'payment_method'       => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'payment_date'         => $this->payment_date?->format('Y-m-d'),
            'status'               => $this->status,
            'submitted_by_role'    => $this->submitted_by_role,
            'rejection_reason'     => $this->rejection_reason,
            'payment_slip_url'     => $this->payment_slip_url,
            'verified_at'          => $this->verified_at?->toDateTimeString(),
            'notes'                => $this->notes,
            'child'                => $this->whenLoaded('child', fn () => [
                'id'        => $this->child->id,
                'full_name' => $this->child->full_name,
            ]),
            'enrollment'           => $this->whenLoaded('enrollment', fn () => [
                'id'           => $this->enrollment->id,
                'final_total'  => $this->enrollment->final_total,
                'paid_amount'  => $this->enrollment->paid_amount,
                'remaining_amount' => $this->enrollment->remaining_amount,
            ]),
            'received_by'          => $this->whenLoaded('receivedBy', fn () => $this->receivedBy?->full_name),
            'verified_by'          => $this->whenLoaded('verifiedBy', fn () => $this->verifiedBy?->full_name),
            'created_at'           => $this->created_at?->toDateTimeString(),
        ];
    }
}
