<?php

namespace Database\Factories;

use App\Models\PrescriptionItem;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$medicine, $dosage] = fake()->randomElement([
            ['Paracetamol', '500 mg'],
            ['Ibuprofen', '400 mg'],
            ['Amoxicillin', '500 mg'],
            ['Cetirizine', '10 mg'],
            ['Omeprazole', '20 mg'],
        ]);

        return [
            'visit_id' => Visit::factory(),
            'medicine' => $medicine,
            'dosage' => $dosage,
            'frequency' => fake()->randomElement(PrescriptionItem::FREQUENCIES),
            'duration' => fake()->randomElement(PrescriptionItem::DURATIONS),
            'instructions' => fake()->optional()->randomElement(['After meals', 'Before meals', 'With plenty of water']),
        ];
    }
}
