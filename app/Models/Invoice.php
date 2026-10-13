<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An invoice. Amounts are integers in the currency's smallest unit.
 *
 * @property int $id
 * @property string $invoice_number
 * @property int $number_year
 * @property int $number_sequence
 * @property int $patient_id
 * @property int|null $visit_id
 * @property CarbonImmutable $issued_at
 * @property int $subtotal
 * @property int $discount
 * @property int $total
 * @property InvoiceStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $voided_at
 * @property int|null $voided_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Patient $patient
 * @property-read Visit|null $visit
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Payment> $payments
 * @property-read User|null $creator
 * @property-read User|null $voider
 */
#[Fillable(['notes'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'unpaid',
        'subtotal' => 0,
        'discount' => 0,
        'total' => 0,
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // Number each new invoice for the (clinic-local) year it's issued in.
        static::creating(function (Invoice $invoice): void {
            if (! isset($invoice->invoice_number)) {
                $invoice->assignNumber(app(ClinicSchedule::class)->toLocal($invoice->issued_at)->year);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
            'number_year' => 'integer',
            'number_sequence' => 'integer',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'status' => InvoiceStatus::class,
        ];
    }

    /**
     * Get the patient billed, even if their record was deleted.
     *
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Get the visit the invoice is for, if any.
     *
     * @return BelongsTo<Visit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Get the invoice lines, in the order they were added.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    /**
     * Get the payments made towards the invoice, oldest first.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at')->orderBy('id');
    }

    /**
     * Get the member of staff who created the invoice.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the admin who voided the invoice.
     *
     * @return BelongsTo<User, $this>
     */
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Calculate the subtotal of invoice lines.
     *
     * @param  iterable<array{quantity: int, unit_price: int}>  $items
     */
    public static function subtotalOf(iterable $items): int
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        return $subtotal;
    }

    /**
     * Set the subtotal and total from the given lines and the discount.
     *
     * @param  iterable<array{quantity: int, unit_price: int}>  $items
     */
    public function applyTotals(iterable $items): void
    {
        $this->subtotal = self::subtotalOf($items);
        $this->total = max($this->subtotal - $this->discount, 0);
    }

    /**
     * Give the invoice the next number for the year, e.g. INV-2026-0001.
     */
    public function assignNumber(int $year): void
    {
        $this->number_year = $year;
        $this->number_sequence = (int) self::query()->where('number_year', $year)->max('number_sequence') + 1;
        $this->invoice_number = sprintf('INV-%d-%04d', $year, $this->number_sequence);
    }

    /**
     * Get the amount paid so far, from withSum('payments', 'amount') when it
     * was loaded, otherwise from the payments.
     */
    public function amountPaid(): int
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            return (int) $this->attributes['payments_sum_amount'];
        }

        return (int) $this->payments->sum('amount');
    }

    /**
     * Get the amount still to pay.
     */
    public function balance(): int
    {
        return max($this->total - $this->amountPaid(), 0);
    }

    /**
     * Update the status from the payments made. Void invoices stay void.
     */
    public function refreshStatus(): void
    {
        if ($this->status === InvoiceStatus::Void) {
            return;
        }

        $this->status = InvoiceStatus::fromPayments((int) $this->payments()->sum('amount'), $this->total);
        $this->save();
    }

    /**
     * Limit invoices to patients matching a name or MRN.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forPatientMatching(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $query->whereHas('patient', fn (Builder $patients) => $patients->search($term));
    }

    /**
     * Limit invoices to the ones issued within the given period.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function issuedBetween(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query
            ->when($from, fn (Builder $query, CarbonInterface $from) => $query->where('issued_at', '>=', CarbonImmutable::instance($from)->utc()))
            ->when($to, fn (Builder $query, CarbonInterface $to) => $query->where('issued_at', '<=', CarbonImmutable::instance($to)->utc()));
    }
}
