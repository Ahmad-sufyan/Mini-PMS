<?php

namespace App\Http\Requests\Invoices;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Rules\MoneyAmount;
use App\Support\ClinicSchedule;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordPaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clinic = app(ClinicSchedule::class);

        return [
            'amount' => ['required', new MoneyAmount(allowZero: false)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$clinic->toLocal($this->invoice()->issued_at)->toDateString(),
                'before_or_equal:'.$clinic->today()->toDateString(),
            ],
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
            'paid_on.after_or_equal' => 'The payment can\'t be dated before the invoice.',
            'paid_on.before_or_equal' => 'The payment can\'t be dated in the future.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('amount')) {
                    return;
                }

                $balance = $this->invoice()->balance();

                if ($this->amount() > $balance) {
                    $validator->errors()->add('amount', __('The payment can\'t be more than the balance of :balance.', [
                        'balance' => Money::format($balance),
                    ]));
                }
            },
        ];
    }

    /**
     * Get the validated amount in minor units.
     */
    public function amount(): int
    {
        return Money::toMinor((string) $this->input('amount'));
    }

    /**
     * Get the invoice being paid.
     */
    public function invoice(): Invoice
    {
        /** @var Invoice */
        return $this->route('invoice');
    }
}
