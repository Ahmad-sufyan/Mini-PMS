<?php

namespace App\Concerns;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait AppointmentValidationRules
{
    /**
     * Get the validation rules used to validate appointments.
     *
     * @return array<string, array<mixed>>
     */
    protected function appointmentRules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists(Patient::class, 'id')->withoutTrashed()],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where('role', Role::Doctor->value)->where('is_active', true),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', Rule::in(Appointment::DURATIONS)],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'doctor_id' => 'doctor',
            'duration_minutes' => 'duration',
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
            'patient_id.exists' => 'Choose a patient from the list.',
            'doctor_id.exists' => 'Choose an active doctor.',
        ];
    }

    /**
     * Get the start of the appointment (in UTC) from the clinic-local date
     * and time fields.
     */
    public function startsAt(): CarbonImmutable
    {
        return app(ClinicSchedule::class)
            ->at($this->string('date')->value(), $this->string('time')->value())
            ->utc();
    }

    /**
     * Check that the appointment falls within the clinic's opening hours and
     * doesn't overlap another of the doctor's appointments.
     */
    protected function validateSchedule(Validator $validator, ?Appointment $ignore = null): void
    {
        if ($validator->errors()->hasAny(['doctor_id', 'date', 'time', 'duration_minutes'])) {
            return;
        }

        $clinic = app(ClinicSchedule::class);
        $start = $this->startsAt();
        $end = $start->addMinutes($this->integer('duration_minutes'));

        if (! $clinic->isWorkingDay($start)) {
            $validator->errors()->add('date', __('The clinic is closed on :days.', [
                'days' => $clinic->toLocal($start)->format('l').'s',
            ]));

            return;
        }

        if (! $clinic->isWithinOpeningHours($start, $end)) {
            $validator->errors()->add('time', __('Appointments must fit within opening hours (:opens to :closes). This one would run from :start to :end.', [
                'opens' => $clinic->opensAt(),
                'closes' => $clinic->closesAt(),
                'start' => $clinic->toLocal($start)->format('H:i'),
                'end' => $clinic->toLocal($end)->format('H:i'),
            ]));

            return;
        }

        $conflict = Appointment::query()
            ->with('doctor:id,name')
            ->where('doctor_id', $this->integer('doctor_id'))
            ->occupying()
            ->overlapping($start, $end)
            ->when($ignore, fn ($query, Appointment $ignore) => $query->whereKeyNot($ignore->getKey()))
            ->orderBy('starts_at')
            ->first();

        if ($conflict !== null) {
            $validator->errors()->add('time', __(':doctor already has an appointment from :start to :end on :date. Choose another time.', [
                'doctor' => $conflict->doctor->name,
                'start' => $clinic->toLocal($conflict->starts_at)->format('H:i'),
                'end' => $clinic->toLocal($conflict->ends_at)->format('H:i'),
                'date' => $clinic->toLocal($conflict->starts_at)->format('D j M'),
            ]));
        }
    }
}
