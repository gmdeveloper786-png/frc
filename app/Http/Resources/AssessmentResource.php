<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\AssessmentNoteVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $structuredVisible = collect();
        if ($user instanceof User && ! $user->isChild() && ! $user->isFinance()) {
            $structuredVisible = app(AssessmentNoteVisibilityService::class)
                ->visibleNotes($this->resource, $user);
        }

        $showInternalAssessmentText = $user instanceof User && ! $user->isChild() && ! $user->isFinance();

        return [
            'id'                   => $this->id,
            'date'                 => $this->date?->format('Y-m-d'),
            'day'                  => $this->day,
            'time'                 => $this->time,
            'status'               => $this->status,
            'assessment_notes'     => $this->when($showInternalAssessmentText, $this->assessment_notes),
            'cancellation_reason'  => $this->when(
                $user instanceof User && ! $user->isChild() && $this->cancellation_reason,
                $this->cancellation_reason
            ),
            'completed_at'         => $this->completed_at?->toDateTimeString(),
            'cancelled_at'         => $this->cancelled_at?->toDateTimeString(),
            'branch'               => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'services'             => ServiceResource::collection($this->whenLoaded('services')),
            'children'             => UserResource::collection($this->whenLoaded('children')),
            'therapist'            => $this->whenLoaded('therapist', fn () => $this->therapist ? [
                'id'        => $this->therapist->id,
                'full_name' => $this->therapist->full_name,
            ] : null),
            'structured_notes'     => AssessmentNoteResource::collection($structuredVisible),
            /** @deprecated Prefer structured_notes — filtered by role (child/finance receive empty). */
            'notes'                => AssessmentNoteResource::collection($structuredVisible),
            'created_at'           => $this->created_at?->toDateTimeString(),
        ];
    }
}
