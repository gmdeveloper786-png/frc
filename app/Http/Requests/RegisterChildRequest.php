<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'      => ['required', 'string', 'max:255'],
            'father_name'    => ['nullable', 'string', 'max:255'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'age'            => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'date_of_birth'  => ['nullable', 'date', 'before:today'],
            'address'        => ['nullable', 'string', 'max:1000'],
            'phone_number'   => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'parent_notes'   => ['nullable', 'string', 'max:2000'],
            'disability_ids'   => ['nullable', 'array'],
            'disability_ids.*' => ['integer', 'exists:disabilities,id'],
            'other_disability' => ['nullable', 'string', 'max:500'],
        ];
    }
}
