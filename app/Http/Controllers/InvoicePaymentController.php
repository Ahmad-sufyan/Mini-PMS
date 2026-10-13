<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Invoices\RecordPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\ClinicSchedule;
use App\Support\Money;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class InvoicePaymentController extends Controller
{
    /**
     * Record a payment towards an invoice and update its status.
     *
     * @throws ValidationException
     */
    public function __invoke(
        RecordPaymentRequest $request,
        Invoice $invoice,
        #[CurrentUser] User $user,
        ClinicSchedule $clinic,
    ): RedirectResponse {
        $amount = $request->amount();

        $invoice = DB::transaction(function () use ($request, $invoice, $user, $clinic, $amount): Invoice {
            // Re-read the invoice inside the transaction so two payments
            // taken at once can't add up to more than the balance.
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($amount > $invoice->balance()) {
                throw ValidationException::withMessages([
                    'amount' => __('The payment can\'t be more than the balance of :balance.', [
                        'balance' => Money::format($invoice->balance()),
                    ]),
                ]);
            }

            $payment = new Payment([
                'amount' => $amount,
                'method' => $request->validated('method'),
                'paid_at' => $clinic->at($request->string('paid_on')->value(), $clinic->now()->format('H:i'))->utc(),
            ]);
            $payment->received_by = $user->id;

            $invoice->payments()->save($payment);
            $invoice->refreshStatus();

            return $invoice;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $invoice->status === InvoiceStatus::Paid
            ? __('Payment of :amount recorded. :number is paid in full.', [
                'amount' => Money::format($amount),
                'number' => $invoice->invoice_number,
            ])
            : __('Payment of :amount recorded. :balance still to pay.', [
                'amount' => Money::format($amount),
                'balance' => Money::format($invoice->fresh()?->balance() ?? 0),
            ]),
        ]);

        return to_route('invoices.show', $invoice);
    }
}
