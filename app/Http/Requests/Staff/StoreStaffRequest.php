<?php

namespace App\Http\Requests\Staff;

use App\Concerns\PasswordValidationRules;
use App\Concerns\StaffValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    use PasswordValidationRules, StaffValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->staffRules(),
            'password' => $this->passwordRules(),
        ];
    }
}
