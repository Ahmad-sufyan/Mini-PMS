<?php

namespace App\Http\Requests\Visits;

use App\Concerns\VisitValidationRules;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    use VisitValidationRules;

    /**
     * The appointment the visit is for, once looked up.
     */
    private ?Appointment $appointment = null;

    /**
     * Determine if the user is authorized to make this request: only the
     * appointment's doctor, or any doctor for a walk-in visit.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Visit::class, $this->appointment()]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists(Appointment::class, 'id'),
                Rule::unique(Visit::class, 'appointment_id'),
            ],
            'patient_id' => [
                'required_without:appointment_id',
                'nullable',
                'integer',
                Rule::exists(Patient::class, 'id')->withoutTrashed(),
            ],
            ...$this->visitRules(app(ClinicSchedule::class)->today()),
        ];
    }

    /**
     * Get the appointment the visit is for, or null for a walk-in.
     */
    public function appointment(): ?Appointment
    {
        if ($this->appointment === null && $this->integer('appointment_id') > 0) {
            $this->appointment = Appointment::find($this->integer('appointment_id'));
        }

        return $this->appointment;
    }
}
