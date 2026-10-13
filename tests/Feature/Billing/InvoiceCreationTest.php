<?php

use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();
    useClinicCurrency();

    $this->consultation = Service::factory()->create(['code' => Service::CONSULTATION, 'name' => 'Consultation', 'price' => 5000]);
    $this->followUp = Service::factory()->create(['code' => Service::FOLLOW_UP, 'name' => 'Follow-up visit', 'price' => 3000]);

    $this->actingAs(User::factory()->receptionist()->create());
});

test('invoices are numbered in sequence for each year', function () {
    $this->post(route('invoices.store'), invoicePayload())->assertSessionHasNoErrors();
    $this->post(route('invoices.store'), invoicePayload())->assertSessionHasNoErrors();

    Invoice::factory()->withItems([[1, 100]])->create(['issued_at' => '2025-12-30 10:00:00']);

    expect(Invoice::orderBy('id')->pluck('invoice_number')->all())
        ->toBe(['INV-2026-0001', 'INV-2026-0002', 'INV-2025-0001']);
});

test('an invoice can be created for a visit with the consultation prefilled', function () {
    $doctor = User::factory()->doctor()->create(['name' => 'Dr. Ada Test']);
    $visit = Visit::factory()->for($doctor, 'doctor')->create(['visited_at' => '2026-10-05 09:00:00']);

    $this->get(route('invoices.create', ['visit' => $visit->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/create')
            ->where('visit.id', $visit->id)
            ->where('patient.id', $visit->patient_id)
            ->where('items', [[
                'service_id' => $this->consultation->id,
                'description' => 'Consultation with Dr. Ada Test (5 Oct 2026)',
                'quantity' => 1,
                'unit_price' => 5000,
            ]])
            ->has('services', 2),
        );

    $this->post(route('invoices.store'), invoicePayload([
        'visit_id' => $visit->id,
        'items' => [['service_id' => $this->consultation->id, 'description' => 'Consultation with Dr. Ada Test', 'quantity' => 1, 'unit_price' => '50.00']],
    ]))->assertSessionHasNoErrors();

    $invoice = Invoice::sole();

    expect($invoice->visit_id)->toBe($visit->id)
        ->and($invoice->patient_id)->toBe($visit->patient_id)
        ->and($invoice->total)->toBe(5000);
});

test('a follow-up visit is billed as a follow-up', function () {
    $appointment = Appointment::factory()->create(['reason' => 'Follow-up visit']);
    $visit = Visit::factory()->forAppointment($appointment)->create();

    $this->get(route('invoices.create', ['visit' => $visit->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.service_id', $this->followUp->id)
            ->where('items.0.unit_price', 3000),
        );
});

test('a visit is only invoiced once', function () {
    $visit = Visit::factory()->create();
    $invoice = Invoice::factory()->for($visit->patient)->withItems([[1, 5000]])->create(['visit_id' => $visit->id]);

    $this->get(route('invoices.create', ['visit' => $visit->id]))
        ->assertRedirect(route('invoices.show', $invoice));

    $this->post(route('invoices.store'), invoicePayload(['visit_id' => $visit->id]))
        ->assertSessionHasErrors(['visit_id' => "This visit has already been invoiced ({$invoice->invoice_number})."]);

    // Once that invoice is voided, the visit can be invoiced again.
    $invoice->forceFill(['status' => InvoiceStatus::Void])->save();

    $this->post(route('invoices.store'), invoicePayload(['visit_id' => $visit->id]))
        ->assertSessionHasNoErrors();

    expect(Invoice::where('visit_id', $visit->id)->count())->toBe(2);
});

test('the invoice date cannot be in the future', function () {
    $this->post(route('invoices.store'), invoicePayload(['issued_on' => '2999-01-01']))
        ->assertSessionHasErrors(['issued_on' => 'The invoice date can\'t be in the future.']);
});

test('a patient is required without a visit', function () {
    $payload = invoicePayload();
    unset($payload['patient_id']);

    $this->post(route('invoices.store'), $payload)->assertSessionHasErrors('patient_id');
});
