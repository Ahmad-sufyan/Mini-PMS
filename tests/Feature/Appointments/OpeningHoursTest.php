<?php

use App\Models\Appointment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->actingAs(User::factory()->receptionist()->create());
});

test('appointments can fill the day from opening to closing', function (string $time) {
    $this->post(route('appointments.store'), appointmentPayload(['time' => $time]))
        ->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(1);
})->with([
    'first slot' => ['09:00'],
    'last slot' => ['16:30'],
]);

test('appointments cannot start before the clinic opens', function () {
    $this->post(route('appointments.store'), appointmentPayload(['time' => '08:30']))
        ->assertSessionHasErrors([
            'time' => 'Appointments must fit within opening hours (09:00 to 17:00). This one would run from 08:30 to 09:00.',
        ]);

    expect(Appointment::count())->toBe(0);
});

test('appointments cannot run past closing time', function () {
    $this->post(route('appointments.store'), appointmentPayload(['time' => '16:45', 'duration_minutes' => 30]))
        ->assertSessionHasErrors([
            'time' => 'Appointments must fit within opening hours (09:00 to 17:00). This one would run from 16:45 to 17:15.',
        ]);
});

test('appointments cannot be booked on days the clinic is closed', function () {
    $this->post(route('appointments.store'), appointmentPayload(['date' => '2026-10-25']))
        ->assertSessionHasErrors(['date' => 'The clinic is closed on Sundays.']);

    expect(Appointment::count())->toBe(0);
});

test('working days and hours come from the clinic config', function () {
    useClinicHours([
        'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        'opening_hours' => ['opens_at' => '08:00', 'closes_at' => '12:00'],
    ]);

    $this->post(route('appointments.store'), appointmentPayload(['date' => '2026-10-24']))
        ->assertSessionHasErrors(['date' => 'The clinic is closed on Saturdays.']);

    $this->post(route('appointments.store'), appointmentPayload(['time' => '08:00']))
        ->assertSessionHasNoErrors();

    $this->post(route('appointments.store'), appointmentPayload(['time' => '12:00']))
        ->assertSessionHasErrors('time');
});

test('appointments are booked in the clinic timezone and stored in UTC', function () {
    useClinicHours(['timezone' => 'Asia/Karachi']);

    $this->post(route('appointments.store'), appointmentPayload(['time' => '09:00']))
        ->assertSessionHasNoErrors();

    $appointment = Appointment::sole();

    expect($appointment->getRawOriginal('starts_at'))->toBe('2026-10-19 04:00:00')
        ->and($appointment->getRawOriginal('ends_at'))->toBe('2026-10-19 04:30:00');

    // 08:00 in Karachi is 03:00 UTC: before opening, whatever UTC says.
    $this->post(route('appointments.store'), appointmentPayload(['time' => '08:00']))
        ->assertSessionHasErrors('time');

    $this->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments.data.0.date', '2026-10-19')
            ->where('appointments.data.0.start_time', '09:00')
            ->where('appointments.data.0.end_time', '09:30'),
        );
});
