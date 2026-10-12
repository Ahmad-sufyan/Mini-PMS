<?php

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->receptionist()->create());
});

/**
 * Get the ids of the listed patients, in order.
 *
 * @return list<int>
 */
function listedPatientIds(TestResponse $response): array
{
    return array_column($response->inertiaProps('patients.data'), 'id');
}

test('the patient list is paginated', function () {
    Patient::factory()->count(20)->create();

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/index')
            ->has('patients.data', 15)
            ->where('patients.total', 20)
            ->where('patients.last_page', 2)
            ->has('genders', count(Gender::cases())),
        );

    $this->get(route('patients.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('patients.data', 5));
});

test('the list shows each patient\'s age from their date of birth', function () {
    $this->travelTo('2026-06-15 12:00:00');

    Patient::factory()->create(['date_of_birth' => '1990-06-16']);

    $this->get(route('patients.index'))
        ->assertInertia(fn (Assert $page) => $page->where('patients.data.0.age', 35));
});

test('patients can be searched by name, MRN or phone', function () {
    $jane = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '+1 555 123 4567']);
    Patient::factory()->create(['first_name' => 'Omar', 'last_name' => 'Farouk', 'phone' => '+1 555 987 6543']);

    foreach (['jane', 'DOE', 'Jane Doe', $jane->mrn, '123 4567'] as $term) {
        $response = $this->get(route('patients.index', ['search' => $term]));

        expect(listedPatientIds($response))->toBe([$jane->id], "Searching for \"{$term}\"");
        $response->assertInertia(fn (Assert $page) => $page->where('filters.search', $term));
    }
});

test('patients can be filtered by gender', function () {
    $women = Patient::factory()->count(2)->create(['gender' => Gender::Female]);
    Patient::factory()->create(['gender' => Gender::Male]);

    $response = $this->get(route('patients.index', ['gender' => 'female']));

    expect(listedPatientIds($response))->toEqualCanonicalizing($women->pluck('id')->all());
    $response->assertInertia(fn (Assert $page) => $page->where('filters.gender', 'female'));
});

test('patients can be sorted', function () {
    $brown = Patient::factory()->create(['last_name' => 'Brown', 'date_of_birth' => '2015-01-01']);
    $adams = Patient::factory()->create(['last_name' => 'Adams', 'date_of_birth' => '1950-01-01']);
    $clark = Patient::factory()->create(['last_name' => 'Clark', 'date_of_birth' => '1985-01-01']);

    $sorted = fn (array $query): array => listedPatientIds($this->get(route('patients.index', $query)));

    expect($sorted([]))->toBe([$adams->id, $brown->id, $clark->id])
        ->and($sorted(['direction' => 'desc']))->toBe([$clark->id, $brown->id, $adams->id])
        ->and($sorted(['sort' => 'age']))->toBe([$brown->id, $clark->id, $adams->id])
        ->and($sorted(['sort' => 'age', 'direction' => 'desc']))->toBe([$adams->id, $clark->id, $brown->id])
        ->and($sorted(['sort' => 'mrn', 'direction' => 'desc']))->toBe([$clark->id, $adams->id, $brown->id]);
});

test('invalid list filters fall back to the defaults', function () {
    $this->get(route('patients.index', [
        'sort' => 'password',
        'direction' => 'sideways',
        'gender' => 'robot',
        'status' => 'deleted',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('filters', [
            'search' => '',
            'gender' => null,
            'sort' => 'name',
            'direction' => 'asc',
            'status' => 'active',
        ]));
});

test('patients can be registered', function () {
    $response = $this->post(route('patients.store'), patientPayload());

    $patient = Patient::sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('patients.show', $patient))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => "Jane Doe has been registered as {$patient->mrn}.",
        ]);

    expect($patient->full_name)->toBe('Jane Doe')
        ->and($patient->date_of_birth->toDateString())->toBe('1990-04-12')
        ->and($patient->gender)->toBe(Gender::Female)
        ->and($patient->blood_group)->toBe(BloodGroup::OPositive)
        ->and($patient->allergies)->toBe('Penicillin')
        ->and($patient->emergency_contact_name)->toBe('John Doe');
});

test('a patient record can be viewed', function () {
    $patient = Patient::factory()->create(['allergies' => 'Latex']);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/show')
            ->where('patient.id', $patient->id)
            ->where('patient.mrn', $patient->mrn)
            ->where('patient.full_name', $patient->full_name)
            ->where('patient.age', $patient->age)
            ->where('patient.allergies', 'Latex'),
        );
});

test('the edit form is filled with the patient\'s details', function () {
    $patient = Patient::factory()->create(['date_of_birth' => '1985-11-30']);

    $this->get(route('patients.edit', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/edit')
            ->where('patient.date_of_birth', '1985-11-30')
            ->has('genders', count(Gender::cases()))
            ->has('bloodGroups', count(BloodGroup::cases())),
        );
});

test('patient records can be updated', function () {
    $patient = Patient::factory()->create(['blood_group' => BloodGroup::ANegative, 'allergies' => 'Peanuts']);

    $this->put(route('patients.update', $patient), patientPayload([
        'last_name' => 'Smith',
        'blood_group' => '',
        'allergies' => '',
    ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('patients.show', $patient));

    $patient->refresh();

    expect($patient->full_name)->toBe('Jane Smith')
        ->and($patient->blood_group)->toBeNull()
        ->and($patient->allergies)->toBeNull();
});

test('admins can delete and restore patient records', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->create();

    $this->delete(route('patients.destroy', $patient))->assertRedirect(route('patients.index'));
    $this->assertSoftDeleted($patient);

    expect(listedPatientIds($this->get(route('patients.index'))))->toBe([])
        ->and(listedPatientIds($this->get(route('patients.index', ['status' => 'deleted']))))->toBe([$patient->id]);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->whereNot('patient.deleted_at', null));

    $this->patch(route('patients.restore', $patient))->assertRedirect(route('patients.show', $patient));
    $this->assertNotSoftDeleted($patient);
});

test('deleted records cannot be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    $patient = Patient::factory()->trashed()->create();

    $this->get(route('patients.edit', $patient))->assertNotFound();
    $this->put(route('patients.update', $patient), patientPayload())->assertNotFound();
    $this->delete(route('patients.destroy', $patient))->assertNotFound();
});
