<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

beforeEach(function () {
    useClinicHours();

    $this->doctor = User::factory()->doctor()->create(['name' => 'Dr. Ada Test']);
    $this->booked = Appointment::factory()
        ->for($this->doctor, 'doctor')
        ->at('2026-10-19', '10:00', 30)
        ->create();

    $this->actingAs(User::factory()->receptionist()->create());
});

test('a doctor cannot be double booked', function (string $time, int $minutes) {
    $this->post(route('appointments.store'), appointmentPayload([
        'doctor_id' => $this->doctor->id,
        'time' => $time,
        'duration_minutes' => $minutes,
    ]))->assertSessionHasErrors([
        'time' => 'Dr. Ada Test already has an appointment from 10:00 to 10:30 on Mon 19 Oct. Choose another time.',
    ]);

    expect(Appointment::count())->toBe(1);
})->with([
    'the same slot' => ['10:00', 30],
    'starting during it' => ['10:15', 30],
    'ending during it' => ['09:45', 30],
    'surrounding it' => ['09:30', 90],
    'inside it' => ['10:10', 15],
]);

test('back to back appointments are allowed', function (string $time) {
    $this->post(route('appointments.store'), appointmentPayload([
        'doctor_id' => $this->doctor->id,
        'time' => $time,
    ]))->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(2);
})->with([
    'just before' => ['09:30'],
    'just after' => ['10:30'],
]);

test('other doctors can be booked at the same time', function () {
    $this->post(route('appointments.store'), appointmentPayload(['time' => '10:00']))
        ->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(2);
});

test('cancelled and no-show appointments free the slot', function (AppointmentStatus $status) {
    $this->booked->forceFill(['status' => $status])->save();

    $this->post(route('appointments.store'), appointmentPayload([
        'doctor_id' => $this->doctor->id,
        'time' => '10:00',
    ]))->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(2);
})->with([AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);

test('checked in and completed appointments still take up the slot', function (AppointmentStatus $status) {
    $this->booked->forceFill(['status' => $status])->save();

    $this->post(route('appointments.store'), appointmentPayload([
        'doctor_id' => $this->doctor->id,
        'time' => '10:15',
    ]))->assertSessionHasErrors('time');
})->with([AppointmentStatus::CheckedIn, AppointmentStatus::Completed]);

test('rescheduling an appointment does not clash with its own slot', function () {
    $this->put(route('appointments.update', $this->booked), appointmentPayload([
        'patient_id' => $this->booked->patient_id,
        'doctor_id' => $this->doctor->id,
        'time' => '10:15',
    ]))->assertSessionHasNoErrors();

    expect($this->booked->fresh()->starts_at->format('H:i'))->toBe('10:15')
        ->and($this->booked->fresh()->ends_at->format('H:i'))->toBe('10:45');
});

test('rescheduling into another appointment is rejected', function () {
    $other = Appointment::factory()->for($this->doctor, 'doctor')->at('2026-10-19', '14:00')->create();

    $this->put(route('appointments.update', $other), appointmentPayload([
        'patient_id' => $other->patient_id,
        'doctor_id' => $this->doctor->id,
        'time' => '10:20',
    ]))->assertSessionHasErrors('time');

    expect($other->fresh()->starts_at->format('H:i'))->toBe('14:00');
});
