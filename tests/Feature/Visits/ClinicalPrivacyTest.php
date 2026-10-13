<?php

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    $this->doctor = User::factory()->doctor()->create();
    $this->patient = Patient::factory()->create();
    $this->visit = Visit::factory()
        ->for($this->patient)
        ->for($this->doctor, 'doctor')
        ->withPrescription(1)
        ->create([
            'chief_complaint' => 'Confidential complaint',
            'diagnosis' => 'Confidential diagnosis',
            'clinical_notes' => 'Confidential notes',
            'follow_up_date' => '2099-12-01',
        ]);

    $this->visit->prescriptionItems()->update(['medicine' => 'Confidential medicine']);
});

test('receptionists never receive diagnoses, notes or prescriptions', function () {
    $response = $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('patients.show', $this->patient))
        ->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->has('visits', 1)
        ->where('visits.0.id', $this->visit->id)
        ->where('visits.0.clinical', false)
        ->where('visits.0.follow_up_date', '2099-12-01')
        ->where('visits.0.doctor.id', $this->doctor->id)
        ->missing('visits.0.chief_complaint')
        ->missing('visits.0.diagnosis')
        ->missing('visits.0.clinical_notes')
        ->missing('visits.0.vitals')
        ->missing('visits.0.prescriptions'),
    );

    // Not just left out of the props: nowhere in the response at all.
    $response->assertDontSee('Confidential');
});

test('receptionists cannot open a visit or its prescription', function () {
    $this->actingAs(User::factory()->receptionist()->create());

    $this->get(route('visits.edit', $this->visit))->assertForbidden();
    $this->get(route('visits.prescription', $this->visit))->assertForbidden();
});

test('admins can see every visit\'s clinical record', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page
            ->where('visits.0.clinical', true)
            ->where('visits.0.diagnosis', 'Confidential diagnosis')
            ->where('visits.0.clinical_notes', 'Confidential notes')
            ->where('visits.0.prescriptions.0.medicine', 'Confidential medicine')
            ->where('visits.0.can_edit', false)
            ->has('visits.0.vitals.bmi'),
        );

    $this->get(route('visits.prescription', $this->visit))->assertOk();
});

test('doctors only see the visits they recorded', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page->has('visits', 0))
        ->assertDontSee('Confidential');

    $this->get(route('visits.prescription', $this->visit))->assertForbidden();

    $this->actingAs($this->doctor)
        ->get(route('patients.show', $this->patient))
        ->assertInertia(fn (Assert $page) => $page
            ->has('visits', 1)
            ->where('visits.0.diagnosis', 'Confidential diagnosis')
            ->where('visits.0.can_edit', true),
        );
});

test('the printable prescription shows the clinic, doctor, patient and medicines', function () {
    config(['clinic.name' => 'Riverside Family Clinic', 'clinic.address' => '1 River Road']);

    $this->actingAs($this->doctor)
        ->get(route('visits.prescription', $this->visit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/prescription')
            ->where('clinic.name', 'Riverside Family Clinic')
            ->where('clinic.address', '1 River Road')
            ->where('visit.doctor.name', $this->doctor->name)
            ->where('visit.prescriptions.0.medicine', 'Confidential medicine')
            ->where('patient.full_name', $this->patient->full_name)
            ->where('patient.mrn', $this->patient->mrn),
        );
});
