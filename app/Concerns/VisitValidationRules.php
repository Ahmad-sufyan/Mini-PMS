<?php

namespace App\Concerns;

use Carbon\CarbonImmutable;

trait VisitValidationRules
{
    /**
     * Get the validation rules used to validate a visit's clinical record.
     *
     * @return array<string, array<mixed>>
     */
    protected function visitRules(CarbonImmutable $visitDay): array
    {
        return [
            'bp_systolic' => ['nullable', 'integer', 'between:50,260', 'required_with:bp_diastolic'],
            'bp_diastolic' => ['nullable', 'integer', 'between:30,160', 'required_with:bp_systolic', 'lt:bp_systolic'],
            'pulse' => ['nullable', 'integer', 'between:20,250'],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,400'],
            'height_cm' => ['nullable', 'numeric', 'between:30,250'],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date_format:Y-m-d', 'after:'.$visitDay->toDateString()],
            'prescriptions' => ['array', 'max:20'],
            'prescriptions.*.medicine' => ['required', 'string', 'max:255'],
            'prescriptions.*.dosage' => ['required', 'string', 'max:100'],
            'prescriptions.*.frequency' => ['required', 'string', 'max:100'],
            'prescriptions.*.duration' => ['required', 'string', 'max:100'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'patient_id' => 'patient',
            'bp_systolic' => 'systolic pressure',
            'bp_diastolic' => 'diastolic pressure',
            'weight_kg' => 'weight',
            'height_cm' => 'height',
            'chief_complaint' => 'chief complaint',
            'clinical_notes' => 'clinical notes',
            'follow_up_date' => 'follow-up date',
            'prescriptions' => 'prescription',
            'prescriptions.*.medicine' => 'medicine',
            'prescriptions.*.dosage' => 'dosage',
            'prescriptions.*.frequency' => 'frequency',
            'prescriptions.*.duration' => 'duration',
            'prescriptions.*.instructions' => 'instructions',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bp_diastolic.lt' => 'The diastolic pressure must be lower than the systolic pressure.',
            'follow_up_date.after' => 'The follow-up date must be after the visit.',
        ];
    }

    /**
     * Get the validated prescription rows.
     *
     * @return list<array<string, mixed>>
     */
    public function prescriptions(): array
    {
        return array_values($this->validated('prescriptions', []));
    }
}
