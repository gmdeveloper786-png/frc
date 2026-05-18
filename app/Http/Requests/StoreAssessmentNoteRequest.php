<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $assessment = $this->route('assessment');

        if (! $user instanceof \App\Models\User || ! $assessment instanceof Assessment) {
            return false;
        }

        return app(\App\Services\AssessmentNoteVisibilityService::class)->canCreateStructuredNote($user, $assessment);
    }

    public function rules(): array
    {
        return [
            'child_id'                => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'observation'             => ['nullable', 'string', 'max:65000'],
            'recommended_services'    => ['nullable', 'array'],
            'recommended_services.*'  => ['integer', Rule::exists('services', 'id')],
            'child_response'          => ['nullable', 'string', 'max:65000'],
            'initial_recommendation'  => ['nullable', 'string', 'max:65000'],
            'additional_notes'        => ['nullable', 'string', 'max:65000'],
            'status'                  => ['nullable', Rule::in(['draft', 'completed'])],
        ];
    }
}
