<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'password'       => ['required', 'string', 'confirmed', Password::defaults()],
            'age'            => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender'         => ['nullable', 'in:male,female,other'],
            'date_of_birth'  => ['nullable', 'date', 'before:today'],
            'address'        => ['nullable', 'string', 'max:1000'],
            'phone_number'   => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'parent_notes'   => ['nullable', 'string', 'max:2000'],
            'branch_id'      => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('status', 'publish')),
            ],
            'disability_ids'   => ['nullable', 'array'],
            'disability_ids.*' => ['integer', 'exists:disabilities,id'],
            'other_disability' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = array_map('intval', (array) $this->input('disability_ids', []));
            $otherId = \App\Models\Disability::otherId();
            $hasOther = $otherId !== null && in_array((int) $otherId, $ids, true);

            if ($hasOther && ! filled(trim((string) $this->input('other_disability', '')))) {
                $validator->errors()->add('other_disability', 'Please describe the disability when "Other" is selected.');
            }
        });
    }
}
