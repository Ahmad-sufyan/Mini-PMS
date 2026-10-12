<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Allows the usual formatting characters (spaces, dashes, dots, brackets
     * and a leading plus) as long as the number has at least six digits.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            ! is_string($value)
            || ! preg_match('/^\+?[0-9()\-.\s]+$/', $value)
            || preg_match_all('/\d/', $value) < 6
        ) {
            $fail('The :attribute field must be a valid phone number.');
        }
    }
}
