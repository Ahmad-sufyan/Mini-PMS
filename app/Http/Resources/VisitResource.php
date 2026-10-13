<?php

namespace App\Http\Resources;

use App\Models\PrescriptionItem;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Visit
 *
 * @property Visit $resource
 */
class VisitResource extends JsonResource
{
    /**
     * Transform the visit into an array.
     *
     * The clinical details are only added for staff allowed to see them
     * (VisitPolicy::view). For everyone else, such as receptionists, they
     * are left out of the response entirely, not just hidden in the UI.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $clinical = $user?->can('view', $this->resource) ?? false;
        $visitedAt = app(ClinicSchedule::class)->toLocal($this->visited_at);

        $visit = [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'date' => $visitedAt->toDateString(),
            'time' => $visitedAt->format('H:i'),
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
                'specialization' => $this->doctor->specialization,
            ],
            'follow_up_date' => $this->follow_up_date?->toDateString(),
            'clinical' => $clinical,
            'can_edit' => $user?->can('update', $this->resource) ?? false,
        ];

        if (! $clinical) {
            return $visit;
        }

        return [
            ...$visit,
            'chief_complaint' => $this->chief_complaint,
            'diagnosis' => $this->diagnosis,
            'clinical_notes' => $this->clinical_notes,
            'vitals' => [
                'bp_systolic' => $this->bp_systolic,
                'bp_diastolic' => $this->bp_diastolic,
                'pulse' => $this->pulse,
                'temperature' => $this->temperature,
                'weight_kg' => $this->weight_kg,
                'height_cm' => $this->height_cm,
                'bmi' => $this->resource->bmi(),
                'bmi_category' => $this->resource->bmiCategory(),
            ],
            'prescriptions' => $this->prescriptionItems
                ->map(fn (PrescriptionItem $item): array => $item->only([
                    'id',
                    'medicine',
                    'dosage',
                    'frequency',
                    'duration',
                    'instructions',
                ]))
                ->all(),
        ];
    }
}
