<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

dataset('module access', function () {
    $access = [
        'dashboard' => [Role::Admin, Role::Doctor, Role::Receptionist],
        'patients.index' => [Role::Admin, Role::Doctor, Role::Receptionist],
        'appointments.index' => [Role::Admin, Role::Doctor, Role::Receptionist],
        'billing.index' => [Role::Admin, Role::Receptionist],
        'staff.index' => [Role::Admin],
        'staff.create' => [Role::Admin],
    ];

    foreach ($access as $route => $allowedRoles) {
        foreach (Role::cases() as $role) {
            $allowed = in_array($role, $allowedRoles, true);

            yield sprintf('%s %s open %s', $role->value, $allowed ? 'can' : 'cannot', $route) => [$role, $route, $allowed];
        }
    }
});

test('modules are only accessible to the roles allowed to use them', function (Role $role, string $route, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);

    $response = $this->actingAs($user)->get(route($route));

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with('module access');

test('guests are redirected to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'patients.index', 'appointments.index', 'billing.index', 'staff.index']);

test('only admins can manage staff accounts', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);
    $colleague = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('staff.store'), [
        'name' => 'New Hire',
        'email' => 'new.hire@example.com',
        'role' => Role::Admin->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();

    $this->get(route('staff.edit', $colleague))->assertForbidden();
    $this->put(route('staff.update', $colleague), ['name' => 'Changed'])->assertForbidden();
    $this->patch(route('staff.deactivate', $colleague))->assertForbidden();

    expect($colleague->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseMissing('users', ['email' => 'new.hire@example.com']);
})->with([Role::Doctor, Role::Receptionist]);

test('each permission gate follows the role mapping', function (Role $role) {
    $user = User::factory()->create(['role' => $role]);

    foreach (Permission::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission))
            ->toBe($role->hasPermission($permission), "{$role->value} / {$permission->value}");
    }
})->with(Role::cases());

test('inactive users have no permissions', function () {
    $user = User::factory()->admin()->inactive()->create();

    expect($user->permissions())->toBe([])
        ->and(Gate::forUser($user)->allows(Permission::ManageStaff))->toBeFalse();
});

test('the role and permissions are shared with the frontend', function () {
    $doctor = User::factory()->doctor('Pediatrics')->create();

    $this->actingAs($doctor)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('auth.user.role', 'doctor')
            ->where('auth.role', ['value' => 'doctor', 'label' => 'Doctor'])
            ->where('auth.permissions', ['patients.view', 'appointments.view'])
            ->has('clinic.name'),
        );
});

test('admins are granted every permission', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', array_map(
                fn (Permission $permission) => $permission->value,
                Permission::cases(),
            )),
        );
});
