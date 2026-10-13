<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state: an unpaid invoice with no lines.
     * Use withItems() to add lines and work out its totals.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => null,
            'issued_at' => CarbonImmutable::now('UTC'),
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'status' => InvoiceStatus::Unpaid,
            'notes' => null,
        ];
    }

    /**
     * Add lines to the invoice, as [quantity, unit price in minor units],
     * and set its subtotal and total.
     *
     * @param  list<array{int, int}>  $lines
     */
    public function withItems(array $lines, int $discount = 0): static
    {
        if ($lines === []) {
            return $this;
        }

        $subtotal = array_sum(array_map(fn (array $line): int => $line[0] * $line[1], $lines));

        return $this
            ->state(fn (array $attributes) => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => max($subtotal - $discount, 0),
            ])
            ->has(
                InvoiceItem::factory()
                    ->count(count($lines))
                    ->sequence(...array_map(fn (array $line): array => [
                        'quantity' => $line[0],
                        'unit_price' => $line[1],
                    ], $lines)),
                'items',
            );
    }
}
