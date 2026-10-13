<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Models\Invoice;
use App\Models\User;

/**
 * Billing is for admins and receptionists only; doctors have no access.
 */
class InvoicePolicy
{
    /**
     * Determine whether the user can list invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewBilling);
    }

    /**
     * Determine whether the user can view (and print) the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::ViewBilling);
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageBilling);
    }

    /**
     * Determine whether the user can edit the invoice: only while nothing
     * has been paid towards it.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::ManageBilling)
            && $invoice->status === InvoiceStatus::Unpaid;
    }

    /**
     * Determine whether the user can record a payment towards the invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::ManageBilling)
            && in_array($invoice->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true);
    }

    /**
     * Determine whether the user can void the invoice: admins only, and only
     * before any payment was taken, so collected money is never voided.
     */
    public function void(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(Permission::VoidInvoices)
            && $invoice->status === InvoiceStatus::Unpaid;
    }
}
