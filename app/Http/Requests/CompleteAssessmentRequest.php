<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class CompleteAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $assessment = $this->route('assessment');

        if (! $user instanceof \App\Models\User || ! $assessment instanceof Assessment) {
            return false;
        }

        if ($user->hasPermission('manage_assessments')) {
            return true;
        }

        return $user->isTherapist() && (int) $assessment->therapist_id === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'assessment_notes' => ['nullable', 'string', 'max:65000'],
        ];
    }
}
