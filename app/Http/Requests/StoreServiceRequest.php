<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_services') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('service')?->id;

        return [
            'name'   => ['required', 'string', 'max:255', 'unique:services,name' . ($id ? ",{$id}" : '')],
            'status' => ['required', 'in:draft,publish'],
            'feedback_questions'           => ['nullable', 'array'],
            'feedback_questions.*.text'    => ['nullable', 'string', 'max:500'],
            'feedback_questions.*.id'      => ['nullable', 'integer', 'exists:service_feedback_questions,id'],
        ];
    }
}
