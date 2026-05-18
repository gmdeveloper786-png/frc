<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone_number'    => $this->phone_number,
            'whatsapp_number' => $this->whatsapp_number,
            'gender'          => $this->gender,
            'status'          => $this->status,
            'profile'         => $this->whenLoaded('therapistProfile', fn () => [
                'branch'               => $this->therapistProfile->branch?->name,
                'branch_id'            => $this->therapistProfile->branch_id,
                'cnic_number'          => $this->therapistProfile->cnic_number,
                'qualification'        => $this->therapistProfile->qualification,
                'working_days'         => $this->therapistProfile->working_days,
                'available_time_slots' => $this->therapistProfile->available_time_slots,
                'break_time'           => $this->therapistProfile->break_time,
                'profile_status'       => $this->therapistProfile->status,
            ]),
            'services' => $this->whenLoaded('therapistServices', fn () => $this->therapistServices->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
            ])->values()->all()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
