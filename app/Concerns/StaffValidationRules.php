<?php

namespace App\Concerns;

use App\Enums\Role;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;

trait StaffValidationRules
{
    use ProfileValidationRules;

    /**
     * Get the validation rules used to validate staff accounts.
     *
     * The specialization is only kept for doctors; for other roles it is
     * excluded from the validated data.
     *
     * @return array<string, array<mixed>>
     */
    protected function staffRules(?int $userId = null): array
    {
        return [
            ...$this->profileRules($userId),
            'role' => ['required', Rule::enum(Role::class)],
            'specialization' => ['exclude_unless:role,'.Role::Doctor->value, 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', new PhoneNumber],
        ];
    }
}
