<?php

namespace App\Http\Requests\Visits;

use App\Concerns\VisitValidationRules;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitRequest extends FormRequest
{
    use VisitValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $visitDay = app(ClinicSchedule::class)->toLocal($this->visit()->visited_at)->startOfDay();

        return $this->visitRules($visitDay);
    }

    /**
     * Get the visit being updated.
     */
    public function visit(): Visit
    {
        /** @var Visit */
        return $this->route('visit');
    }
}
