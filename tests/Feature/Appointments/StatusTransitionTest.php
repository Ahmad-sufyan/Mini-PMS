<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

beforeEach(function () {
    useClinicHours();
});

dataset('status changes', function () {
    $valid = [
        'scheduled' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['completed'],
    ];

    foreach (AppointmentStatus::cases() as $from) {
        foreach (AppointmentStatus::cases() as $to) {
            $allowed = in_array($to->value, $valid[$from->value] ?? [], true);

            yield sprintf('%s -> %s is %s', $from->value, $to->value, $allowed ? 'allowed' : 'rejected') => [$from, $to, $allowed];
        }
    }
});

test('only the allowed status changes can be made', function (AppointmentStatus $from, AppointmentStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);

    $appointment = Appointment::factory()->status($from)->create();

    $response = $this->actingAs(User::factory()->receptionist()->create())
        ->from(route('appointments.index'))
        ->patch(route('appointments.status', ['appointment' => $appointment, 'status' => $to]));

    $response->assertRedirect(route('appointments.index'));

    if ($allowed) {
        $response->assertSessionHasNoErrors();
        expect($appointment->fresh()->status)->toBe($to);
    } else {
        $response->assertSessionHasErrors('status');
        expect($appointment->fresh()->status)->toBe($from);
    }
})->with('status changes');

test('a visit goes from scheduled to checked in to completed', function () {
    $appointment = Appointment::factory()->create();
    $this->actingAs(User::factory()->receptionist()->create());

    $this->patch(route('appointments.status', [$appointment, 'checked_in']))->assertSessionHasNoErrors();
    $this->patch(route('appointments.status', [$appointment, 'completed']))->assertSessionHasNoErrors();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});

test('an unknown status is not found', function () {
    $appointment = Appointment::factory()->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', [$appointment, 'archived']))
        ->assertNotFound();
});

test('the rejected change is explained', function () {
    $appointment = Appointment::factory()->status(AppointmentStatus::Completed)->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->patch(route('appointments.status', [$appointment, 'cancelled']))
        ->assertSessionHasErrors(['status' => "A completed appointment can't be changed to cancelled."]);
});

test('only scheduled appointments can be edited', function (AppointmentStatus $status, bool $editable) {
    $appointment = Appointment::factory()->status($status)->create();

    $response = $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('appointments.edit', $appointment));

    $editable ? $response->assertOk() : $response->assertForbidden();
})->with([
    'scheduled' => [AppointmentStatus::Scheduled, true],
    'checked in' => [AppointmentStatus::CheckedIn, false],
    'completed' => [AppointmentStatus::Completed, false],
    'cancelled' => [AppointmentStatus::Cancelled, false],
    'no-show' => [AppointmentStatus::NoShow, false],
]);

test('the list only offers the buttons for valid next statuses', function () {
    $appointment = Appointment::factory()->at('2026-10-19', '10:00')->status(AppointmentStatus::CheckedIn)->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('appointments.index', ['from' => '2026-10-19']))
        ->assertInertia(fn ($page) => $page
            ->where('appointments.data.0.id', $appointment->id)
            ->where('appointments.data.0.next_statuses', [['value' => 'completed', 'label' => 'Complete']])
            ->where('appointments.data.0.can_edit', false),
        );
});
