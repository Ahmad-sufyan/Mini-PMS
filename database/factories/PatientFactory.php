<?php

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Common allergies used for fake patients.
     *
     * @var list<string>
     */
    private const array ALLERGIES = [
        'Penicillin',
        'Amoxicillin',
        'Aspirin',
        'Ibuprofen',
        'Sulfa drugs',
        'Latex',
        'Peanuts',
        'Shellfish',
        'Eggs',
        'Pollen',
    ];

    /**
     * Typical front desk notes used for fake patients.
     *
     * @var list<string>
     */
    private const array NOTES = [
        'Prefers morning appointments.',
        'Hard of hearing, please speak clearly.',
        'Needs wheelchair access.',
        'Requires an interpreter.',
        'Anxious about injections.',
        'Prefers to be contacted by email.',
        'Usually accompanied by a carer.',
        'Lives far away, combine appointments where possible.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = collect(Gender::cases())->random();
        $registeredAt = fake()->dateTimeBetween('-2 years');

        return [
            'first_name' => fake()->firstName(match ($gender) {
                Gender::Male => 'male',
                Gender::Female => 'female',
                Gender::Other => null,
            }),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-90 years', '-1 month')->format('Y-m-d'),
            'gender' => $gender,
            'phone' => fake()->numerify('+1 555 ### ####'),
            'email' => fake()->optional(0.7)->safeEmail(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'blood_group' => fake()->optional(0.8)->randomElement(BloodGroup::cases()),
            'allergies' => fake()->boolean(25)
                ? implode(', ', fake()->randomElements(self::ALLERGIES, fake()->numberBetween(1, 3)))
                : null,
            'notes' => fake()->optional(0.3)->randomElement(self::NOTES),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('+1 555 ### ####'),
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ];
    }
}
