<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Pin the clinic's schedule so tests don't depend on the local .env:
 * Monday to Saturday, 09:00 to 17:00, UTC unless overridden.
 *
 * @param  array<string, mixed>  $overrides
 */
function useClinicHours(array $overrides = []): void
{
    config([
        'clinic.timezone' => 'UTC',
        'clinic.opening_hours' => ['opens_at' => '09:00', 'closes_at' => '17:00'],
        'clinic.working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
        ...collect($overrides)->mapWithKeys(fn ($value, $key) => ["clinic.{$key}" => $value])->all(),
    ]);
}

/**
 * Valid form data for booking an appointment on Monday 19 October 2026 at
 * 10:00. A patient and doctor are created unless given.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function appointmentPayload(array $overrides = []): array
{
    return [
        'date' => '2026-10-19',
        'time' => '10:00',
        'duration_minutes' => 30,
        'reason' => 'Follow-up visit',
        'notes' => '',
        ...$overrides,
        'patient_id' => $overrides['patient_id'] ?? Patient::factory()->create()->id,
        'doctor_id' => $overrides['doctor_id'] ?? User::factory()->doctor()->create()->id,
    ];
}

/**
 * Valid form data for registering or updating a (fake) patient.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function patientPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-04-12',
        'gender' => 'female',
        'phone' => '+1 555 010 0200',
        'email' => 'jane.doe@example.com',
        'address' => '1 Test Street, Springfield',
        'blood_group' => 'O+',
        'allergies' => 'Penicillin',
        'notes' => 'Prefers morning appointments.',
        'emergency_contact_name' => 'John Doe',
        'emergency_contact_phone' => '+1 555 010 0201',
        ...$overrides,
    ];
}
