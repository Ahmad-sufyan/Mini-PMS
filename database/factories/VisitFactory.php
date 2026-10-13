<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state: a walk-in visit last week.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => null,
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory()->doctor(),
            'visited_at' => CarbonImmutable::now('UTC')->subWeek(),
            'bp_systolic' => fake()->numberBetween(105, 140),
            'bp_diastolic' => fake()->numberBetween(65, 90),
            'pulse' => fake()->numberBetween(60, 100),
            'temperature' => fake()->randomFloat(1, 36.3, 37.3),
            'weight_kg' => fake()->randomFloat(1, 50, 95),
            'height_cm' => fake()->randomFloat(1, 150, 190),
            'chief_complaint' => 'Cough for three days',
            'diagnosis' => 'Acute upper respiratory tract infection',
            'clinical_notes' => 'No red flags on examination. Advised rest and fluids.',
            'follow_up_date' => null,
        ];
    }

    /**
     * Record the visit for the given appointment, by its doctor.
     */
    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'visited_at' => $appointment->starts_at,
        ]);
    }

    /**
     * Prescribe the given number of medicines at the visit.
     */
    public function withPrescription(int $items = 2): static
    {
        return $this->has(PrescriptionItem::factory()->count($items), 'prescriptionItems');
    }
}
