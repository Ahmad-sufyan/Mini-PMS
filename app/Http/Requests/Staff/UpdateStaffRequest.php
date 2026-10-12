<?php

namespace App\Http\Requests\Staff;

use App\Concerns\StaffValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateStaffRequest extends FormRequest
{
    use StaffValidationRules;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // An unchecked checkbox is not submitted at all.
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->staffRules($this->staffMember()->id),
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
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
                if (! $this->staffMember()->is($this->user())) {
                    return;
                }

                if ($this->input('role') !== $this->staffMember()->role->value) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('You cannot deactivate your own account.'));
                }
            },
        ];
    }

    /**
     * Get the staff member being updated.
     */
    public function staffMember(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
