<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Support\ClinicSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 *
 * @property Invoice $resource
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the invoice into an array. Amounts are in minor units;
     * the lines and payments are only included when they were loaded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $clinic = app(ClinicSchedule::class);
        $user = $request->user();

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'issued_on' => $clinic->toLocal($this->issued_at)->toDateString(),
            'patient' => [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'full_name' => $this->patient->full_name,
            ],
            'visit_id' => $this->visit_id,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'paid' => $this->resource->amountPaid(),
            'balance' => $this->resource->balance(),
            'notes' => $this->notes,
            'voided_on' => $this->voided_at ? $clinic->toLocal($this->voided_at)->toDateString() : null,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (InvoiceItem $item): array => [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount(),
            ])->all()),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->method->value,
                'method_label' => $payment->method->label(),
                'paid_on' => $clinic->toLocal($payment->paid_at)->toDateString(),
                'received_by' => $payment->receiver?->name,
            ])->all()),
            'can' => [
                'update' => $user?->can('update', $this->resource) ?? false,
                'record_payment' => $user?->can('recordPayment', $this->resource) ?? false,
                'void' => $user?->can('void', $this->resource) ?? false,
            ],
        ];
    }
}
