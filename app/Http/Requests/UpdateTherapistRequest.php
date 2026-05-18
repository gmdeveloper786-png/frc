<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTherapistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_therapists') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('service_ids');
        if ($raw !== null && ! is_array($raw)) {
            $this->merge(['service_ids' => [$raw]]);
        }
    }

    private function editingTherapistId(): ?int
    {
        foreach (['therapist', 'id'] as $key) {
            $v = $this->route($key);
            if ($v !== null) {
                return (int) $v;
            }
        }

        return null;
    }

    public function rules(): array
    {
        $publishedService = Rule::exists('services', 'id')->where('status', 'publish');
        $id               = $this->editingTherapistId();

        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
            'branch_id'        => ['required', 'exists:branches,id'],
            'service_ids'      => ['required', 'array', 'min:1'],
            'service_ids.*'    => ['integer', $publishedService],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'whatsapp_number'  => ['nullable', 'string', 'max:20'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date'],
            'cnic_number'      => ['nullable', 'string', 'max:20'],
            'address'          => ['nullable', 'string', 'max:1000'],
            'qualification'    => ['nullable', 'string', 'max:255'],
            'working_days'     => ['nullable', 'array'],
            'working_days.*'   => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'slot_start'       => ['required', 'date_format:H:i'],
            'slot_end'         => ['required', 'date_format:H:i', 'after:slot_start'],
            'break_start'      => ['nullable', 'date_format:H:i'],
            'break_end'        => ['nullable', 'date_format:H:i', 'after:break_start'],
            'documents'        => ['nullable', 'array'],
            'documents.*'      => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
            'profile_status'   => ['nullable', 'in:active,inactive'],
            'status'           => ['nullable', 'in:active,inactive'],
        ];
    }
}
