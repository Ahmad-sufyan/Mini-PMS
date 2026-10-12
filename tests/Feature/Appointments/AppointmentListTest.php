<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Support\ClinicSchedule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->actingAs(User::factory()->receptionist()->create());
});

/**
 * Get the ids of the listed appointments, in order.
 *
 * @return list<int>
 */
function listedAppointmentIds(TestResponse $response, string $prop = 'appointments.data'): array
{
    return array_column($response->inertiaProps($prop), 'id');
}

test('the list shows today\'s appointments by default', function () {
    $this->travelTo('2026-10-19 08:00:00');

    $first = Appointment::factory()->at('2026-10-19', '09:00')->create();
    $second = Appointment::factory()->at('2026-10-19', '16:00')->create();
    Appointment::factory()->at('2026-10-20', '09:00')->create();
    Appointment::factory()->at('2026-10-17', '09:00')->create();

    $response = $this->get(route('appointments.index'))->assertOk();

    expect(listedAppointmentIds($response))->toBe([$first->id, $second->id]);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('appointments/index')
        ->where('filters.from', '2026-10-19')
        ->where('filters.to', '2026-10-19')
        ->where('today', '2026-10-19'),
    );
});

test('"today" follows the clinic timezone', function () {
    // 21:00 UTC on Sunday is already Monday in Karachi (UTC+5).
    useClinicHours(['timezone' => 'Asia/Karachi']);
    $this->travelTo('2026-10-18 21:00:00');

    $this->get(route('appointments.index'))
        ->assertInertia(fn (Assert $page) => $page->where('filters.from', '2026-10-19'));
});

test('appointments can be filtered by date range, doctor and status', function () {
    $doctor = User::factory()->doctor()->create();

    $monday = Appointment::factory()->for($doctor, 'doctor')->at('2026-10-19', '09:00')->create();
    $tuesday = Appointment::factory()->for($doctor, 'doctor')->at('2026-10-20', '09:00')->status(AppointmentStatus::Cancelled)->create();
    $otherDoctor = Appointment::factory()->at('2026-10-20', '10:00')->create();
    Appointment::factory()->at('2026-10-22', '09:00')->create();

    $list = fn (array $query): array => listedAppointmentIds($this->get(route('appointments.index', $query)));

    expect($list(['from' => '2026-10-19', 'to' => '2026-10-20']))->toBe([$monday->id, $tuesday->id, $otherDoctor->id])
        ->and($list(['from' => '2026-10-19', 'to' => '2026-10-20', 'doctor' => $doctor->id]))->toBe([$monday->id, $tuesday->id])
        ->and($list(['from' => '2026-10-19', 'to' => '2026-10-20', 'status' => 'cancelled']))->toBe([$tuesday->id]);
});

test('invalid filters fall back to the defaults', function () {
    $this->travelTo('2026-10-19 08:00:00');

    $this->get(route('appointments.index', ['from' => 'yesterday', 'to' => '2026-02-31', 'status' => 'archived', 'doctor' => 'x']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters', [
            'from' => '2026-10-19',
            'to' => '2026-10-19',
            'doctor' => null,
            'status' => null,
        ]));
});

test('a reversed date range is swapped', function () {
    $this->get(route('appointments.index', ['from' => '2026-10-25', 'to' => '2026-10-19']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.from', '2026-10-19')
            ->where('filters.to', '2026-10-25'),
        );
});

test('the week view shows Monday to Sunday of the chosen week', function () {
    $inWeek = Appointment::factory()->at('2026-10-21', '09:00')->create();
    Appointment::factory()->at('2026-10-26', '09:00')->create();
    Appointment::factory()->at('2026-10-17', '09:00')->create();

    $response = $this->get(route('appointments.week', ['week' => '2026-10-23']))->assertOk();

    expect(listedAppointmentIds($response, 'appointments'))->toBe([$inWeek->id]);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('appointments/week')
        ->where('week.start', '2026-10-19')
        ->where('week.previous', '2026-10-12')
        ->where('week.next', '2026-10-26')
        ->has('week.days', 7)
        ->where('week.days.0', ['date' => '2026-10-19', 'is_working_day' => true, 'is_today' => false])
        ->where('week.days.6.is_working_day', false)
        ->where('clinic.closes_at', '17:00'),
    );
});

test('the week view can be filtered by doctor', function () {
    $doctor = User::factory()->doctor()->create();
    $theirs = Appointment::factory()->for($doctor, 'doctor')->at('2026-10-19', '09:00')->create();
    Appointment::factory()->at('2026-10-19', '09:00')->create();

    $response = $this->get(route('appointments.week', ['week' => '2026-10-19', 'doctor' => $doctor->id]));

    expect(listedAppointmentIds($response, 'appointments'))->toBe([$theirs->id]);
});

test('the patient page lists the patient\'s appointments', function () {
    $appointment = Appointment::factory()->at('2026-10-19', '09:00')->create();
    Appointment::factory()->at('2026-10-19', '10:00')->create();

    $this->get(route('patients.show', $appointment->patient_id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments', 1)
            ->where('appointments.0.id', $appointment->id)
            ->has('today'),
        );
});

test('the seeder books realistic appointments without clashes', function () {
    $this->travelTo('2026-10-21 12:00:00');
    $this->seed(DatabaseSeeder::class);

    $appointments = Appointment::all();
    $now = now();

    expect($appointments)->not->toBeEmpty()
        ->and($appointments->min('starts_at')->toDateString())->toBe('2026-08-22')
        ->and($appointments->max('starts_at')->toDateString())->toBe('2026-11-04');

    $clinic = app(ClinicSchedule::class);
    $occupying = AppointmentStatus::occupying();

    foreach ($appointments as $appointment) {
        expect($clinic->isWithinOpeningHours($appointment->starts_at, $appointment->ends_at))->toBeTrue();

        if ($appointment->ends_at->lte($now)) {
            expect($appointment->status)->toBeIn([AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);
        } elseif ($appointment->starts_at->gt($now)) {
            expect($appointment->status)->toBeIn([AppointmentStatus::Scheduled, AppointmentStatus::Cancelled]);
        }
    }

    $clashes = $appointments
        ->filter(fn (Appointment $appointment) => in_array($appointment->status, $occupying, true))
        ->groupBy('doctor_id')
        ->sum(fn ($booked) => $booked->sortBy('starts_at')->values()->sliding(2)
            ->filter(fn ($pair) => $pair->first()->ends_at->gt($pair->last()->starts_at))
            ->count());

    expect($clashes)->toBe(0);
});
