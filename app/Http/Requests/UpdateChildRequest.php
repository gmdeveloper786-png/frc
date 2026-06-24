<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_children') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'disability_ids'    => $this->input('disability_ids', []),
            'remove_documents'  => $this->input('remove_documents', []),
        ]);
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        $rules = [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password'         => ['nullable', 'string', 'confirmed', Password::defaults()],
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

        if ($this->user()?->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $rules['documents']          = ['nullable', 'array'];
            $rules['documents.*']        = UploadRules::document(required: false);
            $rules['remove_documents']   = ['nullable', 'array'];
            $rules['remove_documents.*'] = ['string', 'max:500'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = array_map('intval', (array) $this->input('disability_ids', []));
            $otherId = \App\Models\Disability::otherId();
            $hasOther = $otherId !== null && in_array((int) $otherId, $ids, true);

            if ($hasOther && ! filled(trim((string) $this->input('other_disability', '')))) {
                $validator->errors()->add('other_disability', 'Please describe the present complaint when "Other" is selected.');
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

            if ($this->user()?->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
                $existing = is_array($child->documents) ? $child->documents : [];
                $toRemove = array_values(array_filter(
                    (array) $this->input('remove_documents', []),
                    fn ($path) => is_string($path) && $path !== '',
                ));

                foreach ($toRemove as $path) {
                    if (! in_array($path, $existing, true)) {
                        $validator->errors()->add('remove_documents', 'One or more selected documents could not be found.');
                        break;
                    }

                    if (! str_starts_with($path, 'children/documents/')) {
                        $validator->errors()->add('remove_documents', 'Invalid document selected for removal.');
                        break;
                    }
                }
            }
        });
    }
}
