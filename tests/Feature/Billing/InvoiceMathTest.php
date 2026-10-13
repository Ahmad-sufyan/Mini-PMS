<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Support\Money;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();
    useClinicCurrency();

    $this->actingAs(User::factory()->receptionist()->create());
});

test('amounts typed into forms are stored in minor units', function (string $typed, int $minor) {
    expect(Money::toMinor($typed))->toBe($minor);
})->with([
    ['12.50', 1250],
    ['12.5', 1250],
    ['0.05', 5],
    ['100', 10000],
    ['7.1', 710],
    ['1999.99', 199999],
]);

test('currencies without decimals are stored as whole units', function () {
    useClinicCurrency('JPY');

    expect(Money::decimals())->toBe(0)
        ->and(Money::toMinor('1250'))->toBe(1250)
        ->and(Money::format(1250))->toBe('JPY 1,250');

    $this->post(route('invoices.store'), invoicePayload([
        'items' => [['description' => 'Consultation', 'quantity' => 1, 'unit_price' => '12.50']],
    ]))->assertSessionHasErrors(['items.0.unit_price' => 'The price must be an amount like 13.']);
});

test('invoice totals are worked out from the lines and the discount', function () {
    $ecg = Service::factory()->create(['price' => 4000]);

    $this->post(route('invoices.store'), invoicePayload([
        'discount' => '10.00',
        'items' => [
            ['service_id' => '', 'description' => 'Blood test', 'quantity' => 2, 'unit_price' => '25.00'],
            ['service_id' => $ecg->id, 'description' => 'ECG', 'quantity' => 1, 'unit_price' => '40.00'],
            ['service_id' => '', 'description' => 'Dressing', 'quantity' => 3, 'unit_price' => '1.99'],
        ],
    ]))->assertSessionHasNoErrors();

    $invoice = Invoice::with('items')->sole();

    expect($invoice->subtotal)->toBe(5000 + 4000 + 597)
        ->and($invoice->discount)->toBe(1000)
        ->and($invoice->total)->toBe(8597)
        ->and($invoice->balance())->toBe(8597)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->items->map->amount()->all())->toBe([5000, 4000, 597])
        ->and($invoice->items->pluck('service_id')->all())->toBe([null, $ecg->id, null]);
});

test('the discount cannot be more than the subtotal', function () {
    $this->post(route('invoices.store'), invoicePayload(['discount' => '50.01']))
        ->assertSessionHasErrors(['discount' => 'The discount can\'t be more than the subtotal (USD 50.00).']);

    $this->post(route('invoices.store'), invoicePayload(['discount' => '50.00']))
        ->assertSessionHasNoErrors();

    expect(Invoice::sole()->total)->toBe(0);
});

test('an invoice cannot come to more than the billing columns can hold', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'items' => [
            ['service_id' => '', 'description' => 'Typo in the price', 'quantity' => 999, 'unit_price' => '99999999.99'],
        ],
    ]))->assertSessionHasErrors(['items' => 'An invoice can\'t come to more than USD 42,949,672.95.']);

    expect(Invoice::count())->toBe(0);
});

test('invoice lines are validated', function (array $item, string $field) {
    $this->post(route('invoices.store'), invoicePayload([
        'items' => [['service_id' => '', 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => '50.00', ...$item]],
    ]))->assertSessionHasErrors("items.0.{$field}");

    expect(Invoice::count())->toBe(0);
})->with([
    'no description' => [['description' => ''], 'description'],
    'zero quantity' => [['quantity' => 0], 'quantity'],
    'fractional quantity' => [['quantity' => 1.5], 'quantity'],
    'negative price' => [['unit_price' => '-5.00'], 'unit_price'],
    'too many decimals' => [['unit_price' => '12.345'], 'unit_price'],
    'not a number' => [['unit_price' => 'fifty'], 'unit_price'],
]);

test('an invoice needs at least one line', function () {
    $this->post(route('invoices.store'), invoicePayload(['items' => []]))
        ->assertSessionHasErrors(['items' => 'Add at least one line to the invoice.']);
});

test('editing an invoice recalculates its totals', function () {
    $invoice = Invoice::factory()->withItems([[1, 5000]])->create(['issued_at' => '2026-10-05 10:00:00']);

    $this->put(route('invoices.update', $invoice), invoicePayload([
        'discount' => '5.00',
        'items' => [
            ['service_id' => '', 'description' => 'Consultation', 'quantity' => 1, 'unit_price' => '50.00'],
            ['service_id' => '', 'description' => 'X-ray', 'quantity' => 1, 'unit_price' => '60.00'],
        ],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('invoices.show', $invoice));

    $invoice->refresh();

    expect($invoice->subtotal)->toBe(11000)
        ->and($invoice->total)->toBe(10500)
        ->and($invoice->items()->count())->toBe(2);
});

test('the list totals show what was billed, collected and is outstanding', function () {
    $paid = Invoice::factory()->withItems([[1, 5000]])->create(['status' => InvoiceStatus::Paid]);
    Payment::factory()->for($paid)->create(['amount' => 5000]);

    $partial = Invoice::factory()->withItems([[2, 3000]])->create(['status' => InvoiceStatus::Partial]);
    Payment::factory()->for($partial)->create(['amount' => 2500]);

    Invoice::factory()->withItems([[1, 4000]])->create();
    Invoice::factory()->withItems([[1, 9900]])->create(['status' => InvoiceStatus::Void]);

    $this->get(route('invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/index')
            ->has('invoices.data', 4)
            ->where('totals', ['billed' => 15000, 'collected' => 7500, 'outstanding' => 7500]),
        );

    $this->get(route('invoices.index', ['status' => 'partial']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('totals', ['billed' => 6000, 'collected' => 2500, 'outstanding' => 3500]),
        );
});

test('invoices can be filtered by patient and date range', function () {
    $jane = Invoice::factory()
        ->for(Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']))
        ->withItems([[1, 5000]])
        ->create(['issued_at' => '2026-10-02 10:00:00']);
    $other = Invoice::factory()->withItems([[1, 5000]])->create(['issued_at' => '2026-09-15 10:00:00']);

    $ids = fn (array $query) => array_column($this->get(route('invoices.index', $query))->inertiaProps('invoices.data'), 'id');

    expect($ids(['search' => 'jane doe']))->toBe([$jane->id])
        ->and($ids(['search' => $jane->patient->mrn]))->toBe([$jane->id])
        ->and($ids(['from' => '2026-10-01', 'to' => '2026-10-31']))->toBe([$jane->id])
        ->and($ids(['from' => '2026-09-01', 'to' => '2026-09-30']))->toBe([$other->id]);
});
