<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();
    useClinicCurrency();

    $this->patient = Patient::factory()->create();
    $this->invoice = Invoice::factory()->for($this->patient)->withItems([[1, 5000]])->create(['issued_at' => '2026-10-05 10:00:00']);
});

test('doctors have no access to billing', function () {
    $this->actingAs(User::factory()->doctor()->create());

    $this->get(route('invoices.index'))->assertForbidden();
    $this->get(route('invoices.create'))->assertForbidden();
    $this->post(route('invoices.store'), invoicePayload())->assertForbidden();
    $this->get(route('invoices.show', $this->invoice))->assertForbidden();
    $this->get(route('invoices.edit', $this->invoice))->assertForbidden();
    $this->put(route('invoices.update', $this->invoice), invoicePayload())->assertForbidden();
    $this->get(route('invoices.print', $this->invoice))->assertForbidden();
    $this->post(route('invoices.payments.store', $this->invoice), paymentPayload())->assertForbidden();
    $this->patch(route('invoices.void', $this->invoice))->assertForbidden();

    expect(Invoice::count())->toBe(1)
        ->and($this->invoice->fresh()->payments()->count())->toBe(0);
});

test('doctors don\'t receive billing data on the patient page', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->get(route('patients.show', $this->patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('invoices')
            ->missing('uninvoicedVisits')
            ->where('auth.permissions', fn ($permissions) => ! collect($permissions)->contains(fn ($p) => str_starts_with($p, 'billing.'))),
        )
        ->assertDontSee($this->invoice->invoice_number);
});

test('front desk staff can use billing', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $this->get(route('invoices.index'))->assertOk();
    $this->get(route('invoices.show', $this->invoice))->assertOk();
    $this->get(route('invoices.print', $this->invoice))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/invoice')
            ->where('invoice.invoice_number', $this->invoice->invoice_number)
            ->has('invoice.items', 1)
            ->has('clinic.name'),
        );

    $this->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices', 1)
            ->where('invoices.0.id', $this->invoice->id)
            ->has('uninvoicedVisits'),
        );
})->with([Role::Admin, Role::Receptionist]);

test('only admins get the void permission', function () {
    expect(Role::Admin->hasPermission(Permission::VoidInvoices))->toBeTrue()
        ->and(Role::Receptionist->hasPermission(Permission::VoidInvoices))->toBeFalse()
        ->and(Role::Doctor->hasPermission(Permission::ViewBilling))->toBeFalse();
});

test('the patient page lists visits that haven\'t been invoiced', function () {
    $invoiced = Visit::factory()->for($this->patient)->create();
    $this->invoice->forceFill(['visit_id' => $invoiced->id])->save();
    $notInvoiced = Visit::factory()->for($this->patient)->create();

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page
            ->has('uninvoicedVisits', 1)
            ->where('uninvoicedVisits.0.id', $notInvoiced->id),
        );
});
