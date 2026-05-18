<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\AssessmentNote;
use App\Services\AssessmentNoteVisibilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $assessment = $this->route('assessment');
        $note = $this->route('note');

        if (! $user instanceof \App\Models\User || ! $assessment instanceof Assessment || ! $note instanceof AssessmentNote) {
            return false;
        }

        return app(AssessmentNoteVisibilityService::class)->canManageNote($user, $assessment, $note);
    }

    public function rules(): array
    {
        return [
            'observation'             => ['nullable', 'string', 'max:65000'],
            'recommended_services'    => ['nullable', 'array'],
            'recommended_services.*'  => ['integer', Rule::exists('services', 'id')],
            'child_response'          => ['nullable', 'string', 'max:65000'],
            'initial_recommendation'  => ['nullable', 'string', 'max:65000'],
            'additional_notes'        => ['nullable', 'string', 'max:65000'],
            'status'                  => ['nullable', Rule::in(['draft', 'completed'])],
            'child_id'                => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
