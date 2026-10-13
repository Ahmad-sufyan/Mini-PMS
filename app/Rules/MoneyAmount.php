<?php

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MoneyAmount implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(private readonly bool $allowZero = true) {}

    /**
     * Run the validation rule: a positive amount with no more decimals than
     * the clinic's currency has (e.g. "12.50" for USD, "1250" for JPY).
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_int($value) || is_float($value) ? (string) $value : $value;
        $decimals = Money::decimals();
        $pattern = $decimals === 0 ? '/^\d{1,9}$/' : '/^\d{1,9}(\.\d{1,'.$decimals.'})?$/';

        if (! is_string($value) || ! preg_match($pattern, trim($value))) {
            $fail(__('The :attribute must be an amount like :example.', [
                'example' => number_format(12.5, $decimals, '.', ''),
            ]));

            return;
        }

        if (! $this->allowZero && Money::toMinor($value) === 0) {
            $fail('The :attribute must be more than zero.');
        }
    }
}
