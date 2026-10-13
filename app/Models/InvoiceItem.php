<?php

namespace App\Models;

use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line on an invoice: a service from the price list or a custom line.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int|null $service_id
 * @property string $description
 * @property int $quantity
 * @property int $unit_price
 * @property-read Invoice $invoice
 * @property-read Service|null $service
 */
#[Fillable(['service_id', 'description', 'quantity', 'unit_price'])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
        ];
    }

    /**
     * Get the invoice the line is on.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the service the line was picked from, if any.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the line total.
     */
    public function amount(): int
    {
        return $this->quantity * $this->unit_price;
    }
}
