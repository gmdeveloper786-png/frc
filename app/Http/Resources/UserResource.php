<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'full_name'        => $this->full_name,
            'father_name'      => $this->father_name,
            'email'            => $this->email,
            'gr_number'        => $this->gr_number,
            'role'             => $this->whenLoaded('role', fn () => [
                'id'           => $this->role->id,
                'name'         => $this->role->name,
                'display_name' => $this->role->display_name,
            ]),
            'age'              => $this->age,
            'gender'           => $this->gender,
            'date_of_birth'    => $this->date_of_birth?->format('Y-m-d'),
            'address'          => $this->address,
            'phone_number'     => $this->phone_number,
            'whatsapp_number'  => $this->whatsapp_number,
            'parent_notes'     => $this->parent_notes,
            'status'           => $this->status,
            'approved_at'      => $this->approved_at?->toDateTimeString(),
            'rejected_at'      => $this->rejected_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'present_complaints' => DisabilityResource::collection($this->whenLoaded('disabilities')),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}
