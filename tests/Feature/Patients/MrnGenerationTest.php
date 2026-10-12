<?php

use App\Models\Patient;
use App\Models\User;

test('patients are given an MRN when they are created', function () {
    $patient = Patient::factory()->create();

    expect($patient->mrn)->toMatch('/^MRN-\d{6}$/')
        ->toBe(sprintf('MRN-%06d', $patient->id))
        ->and($patient->fresh()->mrn)->toBe($patient->mrn);
});

test('MRNs are zero padded', function (int $id, string $mrn) {
    expect(Patient::formatMrn($id))->toBe($mrn);
})->with([
    [1, 'MRN-000001'],
    [123, 'MRN-000123'],
    [999999, 'MRN-999999'],
    [1000000, 'MRN-1000000'],
]);

test('every patient gets a unique MRN', function () {
    $mrns = Patient::factory()->count(25)->create()->pluck('mrn');

    expect($mrns->filter())->toHaveCount(25)
        ->and($mrns->unique())->toHaveCount(25);
});

test('MRNs are not reused after a record is deleted', function () {
    $softDeleted = Patient::factory()->create();
    $softDeleted->delete();

    $forceDeleted = Patient::factory()->create();
    $forceDeleted->forceDelete();

    $next = Patient::factory()->create();

    expect($next->mrn)->not->toBeIn([$softDeleted->mrn, $forceDeleted->mrn]);
});

test('the MRN cannot be set or changed through the form', function () {
    $this->actingAs(User::factory()->receptionist()->create());

    $this->post(route('patients.store'), patientPayload(['mrn' => 'MRN-999999']))->assertSessionHasNoErrors();

    $patient = Patient::sole();
    $expected = Patient::formatMrn($patient->id);

    expect($patient->mrn)->toBe($expected);

    $this->put(route('patients.update', $patient), patientPayload(['mrn' => 'MRN-123456']))->assertSessionHasNoErrors();

    expect($patient->fresh()->mrn)->toBe($expected);
});
