<?php

namespace App\Http\Requests;

use App\Support\StaffBranchScope;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStaffChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->hasPermission('register_children') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'disability_ids' => $this->input('disability_ids', []),
        ];

        if ($lockedBranch = StaffBranchScope::lockedBranchId($this->user())) {
            $payload['branch_id'] = $lockedBranch;
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', 'confirmed', Password::defaults()],
            'age'              => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date', 'before:today'],
            'address'          => ['nullable', 'string', 'max:1000'],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'whatsapp_number'  => ['nullable', 'string', 'max:20'],
            'parent_notes'     => ['nullable', 'string', 'max:2000'],
            'branch_id'        => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('status', 'publish')),
            ],
            'disability_ids'   => ['nullable', 'array'],
            'disability_ids.*' => ['integer', 'exists:disabilities,id'],
            'other_disability' => ['nullable', 'string', 'max:500'],
            'documents'        => ['nullable', 'array'],
            'documents.*'      => UploadRules::document(required: false),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $staff = $this->user();
            if ($staff === null) {
                return;
            }

            try {
                StaffBranchScope::assertBranchAssignable($staff, (int) $this->input('branch_id'));
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            $ids = array_map('intval', (array) $this->input('disability_ids', []));
            $otherId = \App\Models\Disability::otherId();
            $hasOther = $otherId !== null && in_array((int) $otherId, $ids, true);

            if ($hasOther && ! filled(trim((string) $this->input('other_disability', '')))) {
                $validator->errors()->add('other_disability', 'Please describe the present complaint when "Other" is selected.');
            }
        });
    }
}
