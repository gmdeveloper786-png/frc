<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** API representation of a structured row in `assessment_notes`. */
class AssessmentNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'therapist_id'           => $this->therapist_id,
            'child_id'               => $this->child_id,
            'therapist'              => $this->whenLoaded('therapist', fn () => $this->therapist ? [
                'id'        => $this->therapist->id,
                'full_name' => $this->therapist->full_name,
            ] : null),
            'child'                  => $this->whenLoaded('child', fn () => $this->child ? [
                'id'        => $this->child->id,
                'full_name' => $this->child->full_name,
            ] : null),
            'observation'            => $this->observation,
            'recommended_services'   => $this->recommended_services,
            'child_response'         => $this->child_response,
            'initial_recommendation' => $this->initial_recommendation,
            'additional_notes'       => $this->additional_notes,
            'status'                 => $this->status,
            'created_by'             => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id'        => $this->createdBy->id,
                'full_name' => $this->createdBy->full_name,
            ] : null),
            'created_at'             => $this->created_at?->toDateTimeString(),
            'updated_at'             => $this->updated_at?->toDateTimeString(),
        ];
    }
}
