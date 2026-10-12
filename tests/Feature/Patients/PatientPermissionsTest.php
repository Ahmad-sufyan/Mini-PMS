<?php

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('patient access', function () {
    $everyone = [Role::Admin, Role::Receptionist, Role::Doctor];
    $frontDesk = [Role::Admin, Role::Receptionist];

    $access = [
        'view the list' => $everyone,
        'view a record' => $everyone,
        'view a deleted record' => [Role::Admin],
        'open the registration form' => $frontDesk,
        'register a patient' => $frontDesk,
        'open the edit form' => $frontDesk,
        'update a record' => $frontDesk,
        'delete a record' => [Role::Admin],
        'restore a record' => [Role::Admin],
    ];

    foreach ($access as $action => $allowedRoles) {
        foreach (Role::cases() as $role) {
            $allowed = in_array($role, $allowedRoles, true);

            yield sprintf('%s %s %s', $role->value, $allowed ? 'can' : 'cannot', $action) => [$role, $action, $allowed];
        }
    }
});

test('patient actions are limited by role', function (Role $role, string $action, bool $allowed) {
    $patient = Patient::factory()->create();

    if (in_array($action, ['view a deleted record', 'restore a record'], true)) {
        $patient->delete();
    }

    $this->actingAs(User::factory()->create(['role' => $role]));

    $response = match ($action) {
        'view the list' => $this->get(route('patients.index')),
        'view a record', 'view a deleted record' => $this->get(route('patients.show', $patient)),
        'open the registration form' => $this->get(route('patients.create')),
        'register a patient' => $this->post(route('patients.store'), patientPayload()),
        'open the edit form' => $this->get(route('patients.edit', $patient)),
        'update a record' => $this->put(route('patients.update', $patient), patientPayload()),
        'delete a record' => $this->delete(route('patients.destroy', $patient)),
        'restore a record' => $this->patch(route('patients.restore', $patient)),
    };

    if ($allowed) {
        expect($response->status())->toBeIn([200, 302]);
        $response->assertSessionHasNoErrors();
    } else {
        $response->assertForbidden();
    }
})->with('patient access');

test('doctors cannot change patient records', function () {
    $patient = Patient::factory()->create(['last_name' => 'Original']);

    $this->actingAs(User::factory()->doctor()->create());

    $this->post(route('patients.store'), patientPayload())->assertForbidden();
    $this->put(route('patients.update', $patient), patientPayload(['last_name' => 'Changed']))->assertForbidden();
    $this->delete(route('patients.destroy', $patient))->assertForbidden();

    $this->assertDatabaseCount('patients', 1);
    $this->assertNotSoftDeleted($patient);
    expect($patient->fresh()->last_name)->toBe('Original');
});

test('receptionists cannot delete or restore patient records', function () {
    $patient = Patient::factory()->create();
    $deleted = Patient::factory()->trashed()->create();

    $this->actingAs(User::factory()->receptionist()->create());

    $this->delete(route('patients.destroy', $patient))->assertForbidden();
    $this->patch(route('patients.restore', $deleted))->assertForbidden();

    $this->assertNotSoftDeleted($patient);
    $this->assertSoftDeleted($deleted);
});

test('only admins can list deleted records', function (Role $role, bool $allowed) {
    Patient::factory()->create();
    $deleted = Patient::factory()->trashed()->create();

    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('patients.index', ['status' => 'deleted']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', $allowed ? 'deleted' : 'active')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', fn (int $id) => $allowed === ($id === $deleted->id)),
        );
})->with([
    'admin' => [Role::Admin, true],
    'receptionist' => [Role::Receptionist, false],
    'doctor' => [Role::Doctor, false],
]);

test('guests are redirected to the login page', function () {
    $patient = Patient::factory()->create();

    $this->get(route('patients.index'))->assertRedirect(route('login'));
    $this->get(route('patients.show', $patient))->assertRedirect(route('login'));
    $this->post(route('patients.store'), patientPayload())->assertRedirect(route('login'));
});
