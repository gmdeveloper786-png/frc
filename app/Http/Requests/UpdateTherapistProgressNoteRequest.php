<?php

namespace App\Http\Requests;

use App\Models\ProgressNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTherapistProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTherapist() ?? false;
    }

    public function rules(): array
    {
        return [
            'therapy_goal'        => ['nullable', 'string', 'max:1000'],
            'notes'               => ['required', 'string', 'max:8000'],
            'child_response'      => ['nullable', 'string', 'max:4000'],
            'progress_level'      => ['required', Rule::in(ProgressNote::PROGRESS_LEVELS)],
            'parent_instructions' => ['nullable', 'string', 'max:4000'],
            'next_plan'           => ['nullable', 'string', 'max:4000'],
            'status'              => ['required', Rule::in(ProgressNote::STATUSES)],
        ];
    }
}
