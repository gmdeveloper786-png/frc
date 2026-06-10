<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildListFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_children') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,approved,rejected,active,inactive'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
