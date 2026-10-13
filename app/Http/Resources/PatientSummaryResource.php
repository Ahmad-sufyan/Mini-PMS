<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The few details needed to pick out a patient, e.g. in the patient picker.
 *
 * @mixin Patient
 */
class PatientSummaryResource extends JsonResource
{
    /**
     * Transform the patient into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mrn' => $this->mrn,
            'full_name' => $this->full_name,
            'age' => $this->age,
            'phone' => $this->phone,
        ];
    }
}
