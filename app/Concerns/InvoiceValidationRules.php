<?php

namespace App\Concerns;

use App\Models\Invoice;
use App\Models\Service;
use App\Rules\MoneyAmount;
use App\Support\ClinicSchedule;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait InvoiceValidationRules
{
    /**
     * Get the validation rules for an invoice's date, lines and discount.
     * Amounts are entered as decimals ("12.50") and stored in minor units.
     *
     * @return array<string, array<mixed>>
     */
    protected function invoiceRules(): array
    {
        return [
            'issued_on' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.app(ClinicSchedule::class)->today()->toDateString(),
            ],
            'discount' => ['nullable', new MoneyAmount],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.service_id' => ['nullable', 'integer', Rule::exists(Service::class, 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'between:1,999'],
            'items.*.unit_price' => ['required', new MoneyAmount],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'patient_id' => 'patient',
            'issued_on' => 'invoice date',
            'items' => 'invoice lines',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'price',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line to the invoice.',
            'issued_on.before_or_equal' => 'The invoice date can\'t be in the future.',
        ];
    }

    /**
     * Get the validated lines, with prices in minor units.
     *
     * @return list<array{service_id: int|null, description: string, quantity: int, unit_price: int}>
     */
    public function items(): array
    {
        return $this->toItems($this->validated('items'));
    }

    /**
     * Get the validated discount in minor units.
     */
    public function discount(): int
    {
        return $this->toMinorOrZero($this->validated('discount'));
    }

    /**
     * Check that the discount isn't more than the subtotal. This runs during
     * validation, so it reads the input: validated() would validate again.
     */
    protected function validateTotals(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $subtotal = Invoice::subtotalOf($this->toItems($this->input('items')));

        if ($this->toMinorOrZero($this->input('discount')) > $subtotal) {
            $validator->errors()->add('discount', __('The discount can\'t be more than the subtotal (:subtotal).', [
                'subtotal' => Money::format($subtotal),
            ]));
        }
    }

    /**
     * Convert submitted lines to lines with prices in minor units.
     *
     * @return list<array{service_id: int|null, description: string, quantity: int, unit_price: int}>
     */
    private function toItems(mixed $rows): array
    {
        return array_map(fn (array $item): array => [
            'service_id' => isset($item['service_id']) ? (int) $item['service_id'] : null,
            'description' => (string) $item['description'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => Money::toMinor((string) $item['unit_price']),
        ], array_values(is_array($rows) ? $rows : []));
    }

    /**
     * Convert an optional submitted amount to minor units.
     */
    private function toMinorOrZero(mixed $amount): int
    {
        return $amount === null || $amount === '' ? 0 : Money::toMinor((string) $amount);
    }
}
