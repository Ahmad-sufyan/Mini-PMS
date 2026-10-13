<?php

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->doctor = User::factory()->doctor()->create();
    $this->colleague = User::factory()->doctor()->create();
    $this->patient = Patient::factory()->create();

    $this->own = Appointment::factory()->for($this->doctor, 'doctor')->for($this->patient)->at('2026-10-19', '10:00')->create();
    $this->theirs = Appointment::factory()->for($this->colleague, 'doctor')->for($this->patient)->at('2026-10-19', '11:00')->create();
});

test('doctors only see their own appointments in the list', function () {
    $this->actingAs($this->doctor)
        ->get(route('appointments.index', ['from' => '2026-10-19', 'doctor' => $this->colleague->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.id', $this->own->id)
            ->where('filters.doctor', null)
            ->where('doctors', []),
        );
});

test('doctors only see their own appointments in the week view', function () {
    $this->actingAs($this->doctor)
        ->get(route('appointments.week', ['week' => '2026-10-21']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('appointments/week')
            ->has('appointments', 1)
            ->where('appointments.0.id', $this->own->id),
        );
});

test('doctors only see their own appointments on the patient page', function () {
    $this->actingAs($this->doctor)
        ->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments', 1)
            ->where('appointments.0.id', $this->own->id),
        );
});

test('front desk staff see every doctor\'s appointments', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments.data', 2)
            ->has('doctors', 2),
        );

    $this->get(route('appointments.index', ['from' => '2026-10-19', 'doctor' => $this->colleague->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.id', $this->theirs->id),
        );
})->with([Role::Admin, Role::Receptionist]);

test('doctors can check in and complete their own appointments', function () {
    $this->actingAs($this->doctor);

    $this->patch(route('appointments.status', [$this->own, 'checked_in']))->assertSessionHasNoErrors();
    $this->patch(route('appointments.status', [$this->own, 'completed']))->assertSessionHasNoErrors();

    expect($this->own->fresh()->status)->toBe(AppointmentStatus::Completed);
});

test('doctors cannot change another doctor\'s appointments', function () {
    $this->actingAs($this->doctor)
        ->patch(route('appointments.status', [$this->theirs, 'checked_in']))
        ->assertForbidden();

    expect($this->theirs->fresh()->status)->toBe(AppointmentStatus::Scheduled);
});

test('doctors cannot cancel or mark no-shows', function (string $status) {
    $this->actingAs($this->doctor)
        ->patch(route('appointments.status', [$this->own, $status]))
        ->assertForbidden();

    expect($this->own->fresh()->status)->toBe(AppointmentStatus::Scheduled);
})->with(['cancelled', 'no_show']);

test('doctors only get the check in button on their own appointments', function () {
    $this->actingAs($this->doctor)
        ->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('appointments.data.0.next_statuses', [['value' => 'checked_in', 'label' => 'Check in']])
            ->where('appointments.data.0.can_edit', false),
        );
});

test('doctors cannot book or reschedule appointments', function () {
    $this->actingAs($this->doctor);

    $this->get(route('appointments.create'))->assertForbidden();
    $this->post(route('appointments.store'), appointmentPayload(['doctor_id' => $this->doctor->id]))->assertForbidden();
    $this->get(route('appointments.edit', $this->own))->assertForbidden();
    $this->put(route('appointments.update', $this->own), appointmentPayload(['time' => '15:00']))->assertForbidden();

    expect(Appointment::count())->toBe(2)
        ->and($this->own->fresh()->starts_at->format('H:i'))->toBe('10:00');
});

test('front desk staff can cancel any appointment', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->patch(route('appointments.status', [$this->theirs, 'cancelled']))
        ->assertSessionHasNoErrors();

    expect($this->theirs->fresh()->status)->toBe(AppointmentStatus::Cancelled);
})->with([Role::Admin, Role::Receptionist]);
