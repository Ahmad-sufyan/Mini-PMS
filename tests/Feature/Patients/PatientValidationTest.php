<?php

use App\Models\Patient;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->receptionist()->create());
});

test('required fields must be filled in', function () {
    $this->post(route('patients.store'), [])
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'date_of_birth',
            'gender',
            'phone',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
        ])
        ->assertSessionDoesntHaveErrors(['email', 'blood_group', 'allergies', 'notes']);

    $this->assertDatabaseCount('patients', 0);
});

test('optional fields may be left blank', function () {
    $this->post(route('patients.store'), patientPayload([
        'email' => '',
        'blood_group' => '',
        'allergies' => '',
        'notes' => '',
    ]))->assertSessionHasNoErrors();

    $patient = Patient::sole();

    expect($patient->email)->toBeNull()
        ->and($patient->blood_group)->toBeNull()
        ->and($patient->allergies)->toBeNull()
        ->and($patient->notes)->toBeNull();
});

test('fields are validated', function (string $field, string $value) {
    $this->post(route('patients.store'), patientPayload([$field => $value]))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('patients', 0);
})->with([
    'date of birth in the future' => ['date_of_birth', '2999-01-01'],
    'date of birth before 1900' => ['date_of_birth', '1899-12-31'],
    'date of birth that is not a date' => ['date_of_birth', 'last tuesday'],
    'unknown gender' => ['gender', 'robot'],
    'unknown blood group' => ['blood_group', 'C+'],
    'invalid email' => ['email', 'not-an-email'],
    'phone with letters' => ['phone', 'call me maybe'],
    'phone with too few digits' => ['phone', '12-34'],
    'invalid emergency contact phone' => ['emergency_contact_phone', 'n/a'],
    'first name that is too long' => ['first_name', str_repeat('a', 101)],
    'address that is too long' => ['address', str_repeat('a', 256)],
]);

test('phone numbers get a readable error message', function () {
    $this->post(route('patients.store'), patientPayload(['phone' => 'call me']))
        ->assertSessionHasErrors(['phone' => 'The phone field must be a valid phone number.']);
});

test('updates are validated too', function () {
    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), patientPayload(['first_name' => '', 'gender' => 'robot']))
        ->assertSessionHasErrors(['first_name', 'gender']);

    expect($patient->fresh()->first_name)->toBe($patient->first_name);
});
