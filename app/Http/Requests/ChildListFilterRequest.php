<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->hasPermission('manage_children') || $user->hasPermission('view_children'));
    }

    public function rules(): array
    {
        return [
            'status'          => ['nullable', 'in:pending,approved,rejected,active,inactive'],
            'search'          => ['nullable', 'string', 'max:100'],
            'has_assessments' => ['nullable', 'in:yes,no'],
            'has_enrollments' => ['nullable', 'in:yes,no'],
        ];
    }
}
