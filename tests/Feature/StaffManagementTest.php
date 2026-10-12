<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('admins can see the staff list', function () {
    User::factory()->doctor()->count(2)->create();

    $this->get(route('staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/index')
            ->has('staff', 3)
            ->has('roles', count(Role::cases())),
        );
});

test('admins can create verified staff accounts', function () {
    $response = $this->post(route('staff.store'), [
        'name' => 'Dr. New Doctor',
        'email' => 'new.doctor@example.com',
        'role' => Role::Doctor->value,
        'specialization' => 'Cardiology',
        'phone' => '+1 555 0199',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('staff.index'));

    $user = User::where('email', 'new.doctor@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Doctor)
        ->and($user->specialization)->toBe('Cardiology')
        ->and($user->phone)->toBe('+1 555 0199')
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('doctors need a specialization', function () {
    $this->post(route('staff.store'), [
        'name' => 'Dr. No Specialty',
        'email' => 'doctor@example.com',
        'role' => Role::Doctor->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('specialization');
});

test('only doctors keep a specialization', function () {
    $this->post(route('staff.store'), [
        'name' => 'Front Desk',
        'email' => 'front.desk@example.com',
        'role' => Role::Receptionist->value,
        'specialization' => 'Cardiology',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'front.desk@example.com')->value('specialization'))->toBeNull();
});

test('staff accounts are validated', function () {
    $this->post(route('staff.store'), [
        'name' => '',
        'email' => $this->admin->email,
        'role' => 'surgeon',
        'password' => 'password',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'role', 'password']);
});

test('admins can update staff accounts', function () {
    $doctor = User::factory()->doctor('Pediatrics')->create();
    $originalPassword = $doctor->password;

    $this->put(route('staff.update', $doctor), [
        'name' => 'Renamed Receptionist',
        'email' => $doctor->email,
        'role' => Role::Receptionist->value,
        'phone' => '',
        'password' => '',
        'password_confirmation' => '',
        'is_active' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('staff.index'));

    $doctor->refresh();

    expect($doctor->name)->toBe('Renamed Receptionist')
        ->and($doctor->role)->toBe(Role::Receptionist)
        ->and($doctor->specialization)->toBeNull()
        ->and($doctor->phone)->toBeNull()
        ->and($doctor->password)->toBe($originalPassword);
});

test('admins can reset a staff password and deactivate from the edit form', function () {
    $receptionist = User::factory()->receptionist()->create();

    $this->put(route('staff.update', $receptionist), [
        'name' => $receptionist->name,
        'email' => $receptionist->email,
        'role' => Role::Receptionist->value,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $receptionist->refresh();

    expect(Hash::check('new-password', $receptionist->password))->toBeTrue()
        ->and($receptionist->is_active)->toBeFalse();
});

test('admins can deactivate and reactivate staff accounts', function () {
    $doctor = User::factory()->doctor()->create();

    $this->patch(route('staff.deactivate', $doctor))->assertRedirect(route('staff.index'));
    expect($doctor->fresh()->is_active)->toBeFalse();

    $this->patch(route('staff.activate', $doctor))->assertRedirect(route('staff.index'));
    expect($doctor->fresh()->is_active)->toBeTrue();
});

test('admins cannot deactivate themselves', function () {
    $this->patch(route('staff.deactivate', $this->admin))->assertForbidden();

    $this->put(route('staff.update', $this->admin), [
        'name' => $this->admin->name,
        'email' => $this->admin->email,
        'role' => Role::Admin->value,
    ])->assertSessionHasErrors('is_active');

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

test('admins cannot change their own role', function () {
    $this->put(route('staff.update', $this->admin), [
        'name' => $this->admin->name,
        'email' => $this->admin->email,
        'role' => Role::Receptionist->value,
        'is_active' => '1',
    ])->assertSessionHasErrors('role');

    expect($this->admin->fresh()->role)->toBe(Role::Admin);
});
