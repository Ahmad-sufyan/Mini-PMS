<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->receptionist = User::factory()->receptionist()->create();
    $this->actingAs($this->receptionist);
});

test('the booking form lists active doctors and can preselect a patient', function () {
    $doctor = User::factory()->doctor('Pediatrics')->create();
    User::factory()->doctor()->inactive()->create();
    $patient = Patient::factory()->create();

    $this->get(route('appointments.create', ['patient' => $patient->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/create')
            ->has('doctors', 1)
            ->where('doctors.0.id', $doctor->id)
            ->where('patient.id', $patient->id)
            ->where('patient.mrn', $patient->mrn)
            ->where('durations', Appointment::DURATIONS)
            ->where('clinic.opens_at', '09:00'),
        );
});

test('appointments can be booked', function () {
    $patient = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $doctor = User::factory()->doctor()->create(['name' => 'Dr. Ada Test']);

    $response = $this->post(route('appointments.store'), appointmentPayload([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'duration_minutes' => 45,
        'notes' => 'Bring previous results.',
    ]));

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('appointments.index', ['from' => '2026-10-19', 'to' => '2026-10-19']))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Appointment booked for Jane Doe with Dr. Ada Test on Mon 19 Oct at 10:00.',
        ]);

    $appointment = Appointment::sole();

    expect($appointment->patient_id)->toBe($patient->id)
        ->and($appointment->doctor_id)->toBe($doctor->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->duration_minutes)->toBe(45)
        ->and($appointment->getRawOriginal('starts_at'))->toBe('2026-10-19 10:00:00')
        ->and($appointment->getRawOriginal('ends_at'))->toBe('2026-10-19 10:45:00')
        ->and($appointment->reason)->toBe('Follow-up visit')
        ->and($appointment->notes)->toBe('Bring previous results.')
        ->and($appointment->created_by)->toBe($this->receptionist->id);
});

test('required fields must be filled in', function () {
    $this->post(route('appointments.store'), [])
        ->assertSessionHasErrors(['patient_id', 'doctor_id', 'date', 'time', 'duration_minutes', 'reason'])
        ->assertSessionDoesntHaveErrors('notes');
});

test('appointment fields are validated', function (string $field, mixed $value, string $message) {
    $this->post(route('appointments.store'), appointmentPayload([$field => $value]))
        ->assertSessionHasErrors([$field => $message]);

    expect(Appointment::count())->toBe(0);
})->with([
    'unknown patient' => ['patient_id', 999, 'Choose a patient from the list.'],
    'unknown doctor' => ['doctor_id', 999, 'Choose an active doctor.'],
    'unsupported duration' => ['duration_minutes', 20, 'The selected duration is invalid.'],
    'badly formatted time' => ['time', '10am', 'The time field must match the format H:i.'],
    'badly formatted date' => ['date', '19/10/2026', 'The date field must match the format Y-m-d.'],
]);

test('only active doctors can be booked', function () {
    $inactive = User::factory()->doctor()->inactive()->create();
    $receptionist = User::factory()->receptionist()->create();

    $this->post(route('appointments.store'), appointmentPayload(['doctor_id' => $inactive->id]))
        ->assertSessionHasErrors('doctor_id');

    $this->post(route('appointments.store'), appointmentPayload(['doctor_id' => $receptionist->id]))
        ->assertSessionHasErrors('doctor_id');
});

test('patients with a deleted record cannot be booked', function () {
    $patient = Patient::factory()->trashed()->create();

    $this->post(route('appointments.store'), appointmentPayload(['patient_id' => $patient->id]))
        ->assertSessionHasErrors('patient_id');
});

test('appointments can be rescheduled', function () {
    $appointment = Appointment::factory()->at('2026-10-19', '10:00')->create();
    $doctor = User::factory()->doctor()->create();

    $this->get(route('appointments.edit', $appointment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/edit')
            ->where('appointment.date', '2026-10-19')
            ->where('appointment.start_time', '10:00')
            ->where('patient.id', $appointment->patient_id),
        );

    $this->put(route('appointments.update', $appointment), appointmentPayload([
        'patient_id' => $appointment->patient_id,
        'doctor_id' => $doctor->id,
        'date' => '2026-10-20',
        'time' => '14:30',
        'duration_minutes' => 60,
        'reason' => 'Rescheduled check-up',
    ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('appointments.index', ['from' => '2026-10-20', 'to' => '2026-10-20']));

    $appointment->refresh();

    expect($appointment->doctor_id)->toBe($doctor->id)
        ->and($appointment->getRawOriginal('starts_at'))->toBe('2026-10-20 14:30:00')
        ->and($appointment->getRawOriginal('ends_at'))->toBe('2026-10-20 15:30:00')
        ->and($appointment->reason)->toBe('Rescheduled check-up');
});

test('the patient picker searches patients', function () {
    $jane = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    Patient::factory()->create(['first_name' => 'Omar', 'last_name' => 'Farouk']);

    $this->getJson(route('patients.search', ['query' => 'jane']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $jane->id)
        ->assertJsonPath('data.0.mrn', $jane->mrn)
        ->assertJsonPath('data.0.full_name', 'Jane Doe')
        ->assertJsonStructure(['data' => [['id', 'mrn', 'full_name', 'age', 'phone']]]);
});

test('the patient picker leaves out deleted records and is limited to ten results', function () {
    Patient::factory()->count(12)->create();
    $deleted = Patient::factory()->trashed()->create();

    $response = $this->getJson(route('patients.search'))->assertOk()->assertJsonCount(10, 'data');

    expect(collect($response->json('data'))->pluck('id'))->not->toContain($deleted->id);
});
