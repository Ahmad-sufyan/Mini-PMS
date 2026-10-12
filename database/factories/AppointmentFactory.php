<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state: a 30 minute appointment at 10:00 on
     * the clinic's next working day.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clinic = app(ClinicSchedule::class);
        $day = $clinic->nextWorkingDay($clinic->today()->addDay());

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory()->doctor(),
            'starts_at' => $clinic->at($day->toDateString(), '10:00')->utc(),
            'duration_minutes' => 30,
            'status' => AppointmentStatus::Scheduled,
            'reason' => fake()->randomElement(['Routine check-up', 'Follow-up visit', 'Consultation']),
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Schedule the appointment at a clinic-local date ("Y-m-d") and time ("H:i").
     */
    public function at(string $date, string $time, int $minutes = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => app(ClinicSchedule::class)->at($date, $time)->utc(),
            'duration_minutes' => $minutes,
        ]);
    }

    /**
     * Give the appointment the given status.
     */
    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
