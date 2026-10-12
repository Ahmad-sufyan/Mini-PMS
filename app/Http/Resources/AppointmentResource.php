<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Support\ClinicSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 *
 * @property Appointment $resource
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the appointment into an array. Dates and times are given in
     * the clinic's timezone, which is what staff book and work in.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $clinic = app(ClinicSchedule::class);
        $start = $clinic->toLocal($this->starts_at);
        $user = $request->user();

        return [
            'id' => $this->id,
            'date' => $start->toDateString(),
            'start_time' => $start->format('H:i'),
            'end_time' => $clinic->toLocal($this->ends_at)->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'patient' => [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'full_name' => $this->patient->full_name,
            ],
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
                'specialization' => $this->doctor->specialization,
            ],
            // The status buttons the current user can use on this appointment.
            'next_statuses' => array_values(array_map(
                fn (AppointmentStatus $status): array => ['value' => $status->value, 'label' => $status->actionLabel()],
                array_filter(
                    $this->status->nextStatuses(),
                    fn (AppointmentStatus $status): bool => $user?->can('changeStatus', [$this->resource, $status]) ?? false,
                ),
            )),
            'can_edit' => $user?->can('update', $this->resource) ?? false,
        ];
    }
}
