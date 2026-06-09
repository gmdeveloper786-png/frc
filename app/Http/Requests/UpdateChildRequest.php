<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_children') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'disability_ids' => $this->input('disability_ids', []),
        ]);
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
            'age'              => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date', 'before:today'],
            'address'          => ['nullable', 'string', 'max:1000'],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'whatsapp_number'  => ['nullable', 'string', 'max:20'],
            'parent_notes'     => ['nullable', 'string', 'max:2000'],
            'status'           => ['required', 'in:pending,approved,rejected,active,inactive'],
            'disability_ids'   => ['nullable', 'array'],
            'disability_ids.*' => ['integer', 'exists:disabilities,id'],
            'other_disability' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = array_map('intval', (array) $this->input('disability_ids', []));
            $otherId = \App\Models\Disability::query()->whereRaw('LOWER(name) = ?', ['other'])->value('id');
            $hasOther = $otherId !== null && in_array((int) $otherId, $ids, true);

            if ($hasOther && ! filled(trim((string) $this->input('other_disability', '')))) {
                $validator->errors()->add('other_disability', 'Please describe the disability when "Other" is selected.');
            }
        });

        $validator->after(function ($validator) {
            $id = (int) $this->route('id');
            $child = User::query()->children()->find($id);
            if (! $child || ! $child->isChild()) {
                return;
            }

            $incoming = $this->input('status');

            // Approved is only assignable via Pending Approvals — edits may keep existing approved only.
            if ($incoming === 'approved' && $child->status !== 'approved') {
                $validator->errors()->add(
                    'status',
                    'Approved status can only be set by approving a pending registration from Pending Approvals.',
                );
            }

            // Active requires at least one enrollment that is live or completed.
            if ($incoming === 'active' && ! $child->hasOperationalEnrollment()) {
                $validator->errors()->add(
                    'status',
                    'Active status requires at least one enrollment with status Approved, Active, or Completed. Create or approve an enrollment first.',
                );
            }
        });
    }
}
