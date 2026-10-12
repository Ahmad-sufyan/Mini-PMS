<?php

namespace App\Concerns;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;

trait PatientValidationRules
{
    /**
     * Get the validation rules used to validate patient records.
     *
     * @return array<string, array<mixed>>
     */
    protected function patientRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'after:1900-01-01', 'before_or_equal:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'blood_group' => ['nullable', Rule::enum(BloodGroup::class)],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:30', new PhoneNumber],
        ];
    }
}
