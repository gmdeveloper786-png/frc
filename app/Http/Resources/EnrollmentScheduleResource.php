<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'day'          => $this->day,
            'time_slot'    => $this->time_slot,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'status'       => $this->status,
        ];
    }
}
