<?php

namespace App\Http\Requests\Invoices;

use App\Concerns\InvoiceValidationRules;
use App\Enums\InvoiceStatus;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    use InvoiceValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visit_id' => ['nullable', 'integer', Rule::exists(Visit::class, 'id')],
            'patient_id' => [
                'required_without:visit_id',
                'nullable',
                'integer',
                Rule::exists(Patient::class, 'id')->withoutTrashed(),
            ],
            ...$this->invoiceRules(),
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
                $invoiced = $this->visit()?->invoices()->where('status', '!=', InvoiceStatus::Void)->first();

                if ($invoiced !== null) {
                    $validator->errors()->add('visit_id', __('This visit has already been invoiced (:number).', [
                        'number' => $invoiced->invoice_number,
                    ]));
                }
            },
            fn (Validator $validator) => $this->validateTotals($validator),
        ];
    }

    /**
     * Get the visit the invoice is for, if any.
     */
    public function visit(): ?Visit
    {
        return $this->integer('visit_id') > 0 ? Visit::find($this->integer('visit_id')) : null;
    }
}
