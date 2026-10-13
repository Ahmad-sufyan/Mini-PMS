<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();
    useClinicCurrency();

    $this->receptionist = User::factory()->receptionist()->create();
    $this->invoice = Invoice::factory()->withItems([[1, 5000]])->create(['issued_at' => '2026-10-05 10:00:00']);

    $this->actingAs($this->receptionist);
});

test('the status follows the payments', function () {
    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload(['amount' => '20.00']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('invoices.show', $this->invoice))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Payment of USD 20.00 recorded. USD 30.00 still to pay.',
        ]);

    expect($this->invoice->fresh()->status)->toBe(InvoiceStatus::Partial)
        ->and($this->invoice->fresh()->balance())->toBe(3000);

    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload(['amount' => '30.00', 'method' => 'card']))
        ->assertSessionHasNoErrors();

    $invoice = $this->invoice->fresh();
    $payment = Payment::latest('id')->first();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->balance())->toBe(0)
        ->and($invoice->amountPaid())->toBe(5000)
        ->and($payment->amount)->toBe(3000)
        ->and($payment->method->value)->toBe('card')
        ->and($payment->received_by)->toBe($this->receptionist->id);
});

test('the status is worked out from the amount paid', function (int $paid, InvoiceStatus $status) {
    expect(InvoiceStatus::fromPayments($paid, 5000))->toBe($status);
})->with([
    'nothing paid' => [0, InvoiceStatus::Unpaid],
    'some paid' => [1, InvoiceStatus::Partial],
    'almost all paid' => [4999, InvoiceStatus::Partial],
    'all paid' => [5000, InvoiceStatus::Paid],
]);

test('an overpayment is blocked', function () {
    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload(['amount' => '50.01']))
        ->assertSessionHasErrors(['amount' => 'The payment can\'t be more than the balance of USD 50.00.']);

    expect(Payment::count())->toBe(0)
        ->and($this->invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('payments that add up to more than the total are blocked', function () {
    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload(['amount' => '40.00']))
        ->assertSessionHasNoErrors();

    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload(['amount' => '10.01']))
        ->assertSessionHasErrors(['amount' => 'The payment can\'t be more than the balance of USD 10.00.']);

    expect($this->invoice->fresh()->amountPaid())->toBe(4000);
});

test('payments are validated', function (array $overrides, string $field) {
    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload($overrides))
        ->assertSessionHasErrors($field);

    expect(Payment::count())->toBe(0);
})->with([
    'zero' => [['amount' => '0.00'], 'amount'],
    'negative' => [['amount' => '-5'], 'amount'],
    'unknown method' => [['method' => 'cheque'], 'method'],
    'before the invoice' => [['paid_on' => '2026-10-04'], 'paid_on'],
    'in the future' => [['paid_on' => '2999-01-01'], 'paid_on'],
]);

test('paid and void invoices take no more payments', function (InvoiceStatus $status) {
    $this->invoice->forceFill(['status' => $status])->save();

    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload())
        ->assertForbidden();

    expect(Payment::count())->toBe(0);
})->with([InvoiceStatus::Paid, InvoiceStatus::Void]);

test('invoices can only be edited while unpaid', function () {
    $this->get(route('invoices.edit', $this->invoice))->assertOk();

    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload())->assertSessionHasNoErrors();

    $this->get(route('invoices.edit', $this->invoice))->assertForbidden();
    $this->put(route('invoices.update', $this->invoice), invoicePayload())->assertForbidden();

    expect($this->invoice->fresh()->total)->toBe(5000);
});

test('only admins can void invoices, and only before any payment', function () {
    $this->patch(route('invoices.void', $this->invoice))->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->patch(route('invoices.void', $this->invoice))->assertRedirect(route('invoices.show', $this->invoice));

    $invoice = $this->invoice->fresh();

    expect($invoice->status)->toBe(InvoiceStatus::Void)
        ->and($invoice->voided_by)->toBe($admin->id)
        ->and($invoice->voided_at)->not->toBeNull();

    $partlyPaid = Invoice::factory()->withItems([[1, 5000]])->create(['status' => InvoiceStatus::Partial]);
    $this->patch(route('invoices.void', $partlyPaid))->assertForbidden();
});

test('the invoice page shows the payment history and what the user can do', function () {
    Payment::factory()->for($this->invoice)->create(['amount' => 1500, 'method' => 'cash', 'received_by' => $this->receptionist->id]);
    $this->invoice->refreshStatus();

    $this->get(route('invoices.show', $this->invoice))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/show')
            ->where('invoice.status', 'partial')
            ->where('invoice.paid', 1500)
            ->where('invoice.balance', 3500)
            ->has('invoice.payments', 1)
            ->where('invoice.payments.0.received_by', $this->receptionist->name)
            ->where('invoice.can', ['update' => false, 'record_payment' => true, 'void' => false])
            ->has('paymentMethods', 3),
        );
});
