<?php

namespace App\Http\Requests\Appointments;

use App\Concerns\AppointmentValidationRules;
use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    use AppointmentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->appointmentRules();
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            // The appointment can't clash with its own current time slot.
            fn (Validator $validator) => $this->validateSchedule($validator, $this->appointment()),
        ];
    }

    /**
     * Get the appointment being updated.
     */
    public function appointment(): Appointment
    {
        /** @var Appointment */
        return $this->route('appointment');
    }
}
