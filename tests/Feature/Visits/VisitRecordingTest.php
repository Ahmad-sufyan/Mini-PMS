<?php

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->doctor = User::factory()->doctor()->create();
    $this->appointment = Appointment::factory()
        ->for($this->doctor, 'doctor')
        ->at('2026-10-19', '10:00')
        ->status(AppointmentStatus::CheckedIn)
        ->create();
});

test('the appointment\'s doctor gets "Start visit" instead of Complete', function () {
    $this->actingAs($this->doctor)
        ->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments.data.0.can_start_visit', true)
            ->where('appointments.data.0.next_statuses', []),
        );
});

test('front desk staff keep the Complete button', function () {
    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments.data.0.can_start_visit', false)
            ->where('appointments.data.0.next_statuses', [['value' => 'completed', 'label' => 'Complete']]),
        );
});

test('the doctor can open the visit form for a checked-in appointment', function () {
    $this->actingAs($this->doctor)
        ->get(route('visits.create', ['appointment' => $this->appointment->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('visits/create')
            ->where('appointment.id', $this->appointment->id)
            ->where('patient.id', $this->appointment->patient_id)
            ->has('frequencies')
            ->has('durations'),
        );
});

test('saving a visit completes the appointment', function () {
    $response = $this->actingAs($this->doctor)->post(route('visits.store'), visitPayload([
        'appointment_id' => $this->appointment->id,
        'follow_up_date' => '2099-01-15',
        'prescriptions' => [
            ['medicine' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration' => '5 days', 'instructions' => ''],
            ['medicine' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration' => '7 days', 'instructions' => 'Complete the course'],
        ],
    ]));

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('patients.show', ['patient' => $this->appointment->patient_id, 'tab' => 'visits']));

    $visit = Visit::sole();

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Completed)
        ->and($visit->appointment_id)->toBe($this->appointment->id)
        ->and($visit->patient_id)->toBe($this->appointment->patient_id)
        ->and($visit->doctor_id)->toBe($this->doctor->id)
        ->and($visit->diagnosis)->toBe('Acute bronchitis')
        ->and($visit->follow_up_date?->toDateString())->toBe('2099-01-15')
        ->and($visit->bmi())->toBe(22.9)
        ->and($visit->bmiCategory())->toBe('Healthy weight')
        ->and($visit->prescriptionItems->pluck('medicine')->all())->toBe(['Paracetamol', 'Amoxicillin'])
        ->and($visit->prescriptionItems->first()->instructions)->toBeNull();
});

test('a visit that fails validation leaves the appointment checked in', function () {
    $this->actingAs($this->doctor)
        ->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id, 'diagnosis' => '']))
        ->assertSessionHasErrors('diagnosis');

    expect(Visit::count())->toBe(0)
        ->and($this->appointment->fresh()->status)->toBe(AppointmentStatus::CheckedIn);
});

test('only doctors can record visits', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $this->get(route('visits.create', ['appointment' => $this->appointment->id]))->assertForbidden();
    $this->get(route('visits.create', ['patient' => $this->appointment->patient_id]))->assertForbidden();
    $this->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id]))->assertForbidden();
    $this->post(route('visits.store'), visitPayload(['patient_id' => $this->appointment->patient_id]))->assertForbidden();

    expect(Visit::count())->toBe(0)
        ->and($this->appointment->fresh()->status)->toBe(AppointmentStatus::CheckedIn);
})->with([Role::Admin, Role::Receptionist]);

test('doctors can only record visits for their own checked-in appointments', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id]))
        ->assertForbidden();

    $scheduled = Appointment::factory()->for($this->doctor, 'doctor')->at('2026-10-19', '11:00')->create();

    $this->actingAs($this->doctor)
        ->get(route('visits.create', ['appointment' => $scheduled->id]))
        ->assertForbidden();

    $this->post(route('visits.store'), visitPayload(['appointment_id' => $scheduled->id]))
        ->assertForbidden();

    expect(Visit::count())->toBe(0);
});

test('an appointment only gets one visit', function () {
    $this->actingAs($this->doctor);

    $this->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id]))->assertSessionHasNoErrors();
    $this->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id]))->assertForbidden();

    expect(Visit::count())->toBe(1);
});

test('doctors can record walk-in visits', function () {
    $patient = Patient::factory()->create();

    $this->actingAs($this->doctor)
        ->post(route('visits.store'), visitPayload(['patient_id' => $patient->id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('patients.show', ['patient' => $patient->id, 'tab' => 'visits']));

    $visit = Visit::sole();

    expect($visit->appointment_id)->toBeNull()
        ->and($visit->patient_id)->toBe($patient->id)
        ->and($visit->doctor_id)->toBe($this->doctor->id);
});

test('visits are validated', function (array $overrides, string $field) {
    $this->actingAs($this->doctor)
        ->post(route('visits.store'), visitPayload(['appointment_id' => $this->appointment->id, ...$overrides]))
        ->assertSessionHasErrors($field);

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::CheckedIn);
})->with([
    'no chief complaint' => [['chief_complaint' => ''], 'chief_complaint'],
    'no diagnosis' => [['diagnosis' => ''], 'diagnosis'],
    'diastolic above systolic' => [['bp_systolic' => 80, 'bp_diastolic' => 120], 'bp_diastolic'],
    'only one blood pressure reading' => [['bp_diastolic' => ''], 'bp_diastolic'],
    'impossible temperature' => [['temperature' => 50], 'temperature'],
    'follow-up in the past' => [['follow_up_date' => '2020-01-01'], 'follow_up_date'],
    'medicine without a name' => [['prescriptions' => [['medicine' => '', 'dosage' => '5 mg', 'frequency' => 'Once daily', 'duration' => '5 days']]], 'prescriptions.0.medicine'],
    'medicine without a dosage' => [['prescriptions' => [['medicine' => 'Amlodipine', 'dosage' => '', 'frequency' => 'Once daily', 'duration' => '5 days']]], 'prescriptions.0.dosage'],
]);

test('prescription rows get readable errors', function () {
    $this->actingAs($this->doctor)
        ->post(route('visits.store'), visitPayload([
            'appointment_id' => $this->appointment->id,
            'prescriptions' => [
                ['medicine' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Twice daily', 'duration' => '5 days'],
                ['medicine' => 'Cetirizine', 'dosage' => '10 mg', 'frequency' => '', 'duration' => '7 days'],
            ],
        ]))
        ->assertSessionHasErrors(['prescriptions.1.frequency' => 'The frequency field is required.']);
});

test('doctors can edit their own visits', function () {
    $visit = Visit::factory()->withPrescription(2)->create(['doctor_id' => $this->doctor->id]);

    $this->actingAs($this->doctor)
        ->get(route('visits.edit', $visit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('visits/edit')
            ->where('visit.id', $visit->id)
            ->where('visit.diagnosis', $visit->diagnosis)
            ->has('visit.prescriptions', 2),
        );

    $this->put(route('visits.update', $visit), visitPayload(['diagnosis' => 'Community-acquired pneumonia']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('patients.show', ['patient' => $visit->patient_id, 'tab' => 'visits']));

    expect($visit->fresh()->diagnosis)->toBe('Community-acquired pneumonia')
        ->and($visit->prescriptionItems()->pluck('medicine')->all())->toBe(['Paracetamol']);
});

test('visits can only be edited by the doctor who recorded them', function (string $who) {
    $visit = Visit::factory()->create(['doctor_id' => $this->doctor->id, 'diagnosis' => 'Original']);

    $user = match ($who) {
        'another doctor' => User::factory()->doctor()->create(),
        'an admin' => User::factory()->admin()->create(),
        'a receptionist' => User::factory()->receptionist()->create(),
    };

    $this->actingAs($user)->get(route('visits.edit', $visit))->assertForbidden();
    $this->put(route('visits.update', $visit), visitPayload(['diagnosis' => 'Changed']))->assertForbidden();

    expect($visit->fresh()->diagnosis)->toBe('Original');
})->with(['another doctor', 'an admin', 'a receptionist']);
