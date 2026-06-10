<?php

namespace App\Http\Requests;

use App\Support\StaffBranchScope;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTherapistRequest extends FormRequest
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

        $user = $this->user();
        if ($user && ($locked = StaffBranchScope::lockedBranchId($user))) {
            $this->merge(['branch_id' => $locked]);
        }
    }

    public function rules(): array
    {
        $publishedService = Rule::exists('services', 'id')->where('status', 'publish');

        return [
            'full_name'        => ['required', 'string', 'max:255'],
            'father_name'      => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'confirmed', Password::defaults()],
            'branch_id'        => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('status', 'publish')),
            ],
            'service_ids'      => ['required', 'array', 'min:1'],
            'service_ids.*'    => ['integer', $publishedService],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'whatsapp_number'  => ['nullable', 'string', 'max:20'],
            'gender'           => ['nullable', 'in:male,female,other'],
            'date_of_birth'    => ['nullable', 'date', 'before:today'],
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
            'documents.*'      => UploadRules::document(required: false),
            'profile_status'   => ['nullable', 'in:active,inactive'],
            'status'           => ['nullable', 'in:active,inactive'],
        ];
    }
}
