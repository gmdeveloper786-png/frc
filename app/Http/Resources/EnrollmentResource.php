<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'enrollment_group_id'  => $this->enrollment_group_id,
            'child'              => $this->whenLoaded('child', fn () => [
                'id'        => $this->child->id,
                'full_name' => $this->child->full_name,
                'email'     => $this->child->email,
            ]),
            'branch'             => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'service'            => $this->whenLoaded('service', fn () => [
                'id'   => $this->service->id,
                'name' => $this->service->name,
            ]),
            'therapist'          => $this->whenLoaded('therapist', fn () => [
                'id'        => $this->therapist->id,
                'full_name' => $this->therapist->full_name,
            ]),
            'price_per_session'  => $this->price_per_session,
            'total_sessions'     => $this->total_sessions,
            'subtotal'           => $this->subtotal,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount'    => $this->discount_amount,
            'final_total'        => $this->final_total,
            'paid_amount'        => $this->paid_amount,
            'remaining_amount'   => $this->remaining_amount,
            'payment_status'     => $this->payment_status,
            'repeat_weekly'         => $this->repeat_weekly,
            'schedule_start_date'   => $this->schedule_start_date?->toDateString(),
            'duration_value'        => $this->duration_value,
            'duration_unit'      => $this->duration_unit,
            'status'             => $this->status,
            'rejection_reason'   => $this->when($this->status === 'rejected', $this->rejection_reason),
            'schedules'          => EnrollmentScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
