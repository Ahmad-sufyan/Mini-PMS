<?php

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Database\Seeders\DatabaseSeeder;

// Seeding the full demo data is slow, so one test checks all of it.
test('the demo data is realistic', function () {
    useClinicHours();
    $this->travelTo('2026-10-21 12:00:00');

    $this->seed(DatabaseSeeder::class);

    // Appointments: the past 60 and next 14 days, within opening hours,
    // with statuses that fit the date and no double-booked doctors.
    $appointments = Appointment::all();
    $clinic = app(ClinicSchedule::class);
    $now = now();

    expect($appointments)->not->toBeEmpty()
        ->and($appointments->min('starts_at')->toDateString())->toBe('2026-08-22')
        ->and($appointments->max('starts_at')->toDateString())->toBe('2026-11-04');

    foreach ($appointments as $appointment) {
        expect($clinic->isWithinOpeningHours($appointment->starts_at, $appointment->ends_at))->toBeTrue();

        if ($appointment->ends_at->lte($now)) {
            expect($appointment->status)->toBeIn([AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);
        } elseif ($appointment->starts_at->gt($now)) {
            expect($appointment->status)->toBeIn([AppointmentStatus::Scheduled, AppointmentStatus::Cancelled]);
        }
    }

    $clashes = $appointments
        ->filter(fn (Appointment $appointment) => in_array($appointment->status, AppointmentStatus::occupying(), true))
        ->groupBy('doctor_id')
        ->sum(fn ($booked) => $booked->sortBy('starts_at')->values()->sliding(2)
            ->filter(fn ($pair) => $pair->first()->ends_at->gt($pair->last()->starts_at))
            ->count());

    expect($clashes)->toBe(0);

    // Visits: one, with a prescription, for every completed appointment.
    $completed = Appointment::where('status', AppointmentStatus::Completed)->count();

    expect($completed)->toBeGreaterThan(0)
        ->and(Visit::count())->toBe($completed)
        ->and(Appointment::where('status', AppointmentStatus::Completed)->whereDoesntHave('visit')->count())->toBe(0)
        ->and(Appointment::where('status', '!=', AppointmentStatus::Completed)->whereHas('visit')->count())->toBe(0)
        ->and(Visit::whereDoesntHave('prescriptionItems')->count())->toBe(0);

    Visit::with('appointment')->get()->each(function (Visit $visit) {
        expect($visit->doctor_id)->toBe($visit->appointment->doctor_id)
            ->and($visit->patient_id)->toBe($visit->appointment->patient_id);
    });

    // Billing: the price list, and invoices for most visits with a mix of
    // statuses that match their payments, never overpaid.
    $invoices = Invoice::withSum('payments', 'amount')->get();

    expect(Service::count())->toBe(6)
        ->and($invoices->count())->toBeGreaterThan((int) (Visit::count() * 0.75))
        ->and($invoices->pluck('status')->map->value->unique()->all())->toContain('paid', 'partial', 'unpaid');

    foreach ($invoices as $invoice) {
        $paid = (int) $invoice->payments_sum_amount;

        expect($paid)->toBeLessThanOrEqual($invoice->total)
            ->and($invoice->total)->toBe($invoice->subtotal - $invoice->discount);

        if ($invoice->status !== InvoiceStatus::Void) {
            expect($invoice->status)->toBe(InvoiceStatus::fromPayments($paid, $invoice->total));
        }
    }
});
