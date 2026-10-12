<?php

use App\Models\User;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

test('inactive users cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'This account has been deactivated. Please contact the clinic administrator.',
    ]);
    $this->assertGuest();
});

test('inactive users with two factor enabled are not sent to the challenge', function () {
    $user = User::factory()->inactive()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $response->assertSessionMissing('login.id');
    $this->assertGuest();
});

test('the deactivated message is only shown after the password is verified', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->assertGuest();
});

test('active users can still log in', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('inactive users cannot log in with a passkey', function () {
    $active = (new Passkey)->setRelation('user', User::factory()->create());
    $inactive = (new Passkey)->setRelation('user', User::factory()->inactive()->create());

    expect(Passkeys::allowsLogin(request(), $active))->toBeTrue()
        ->and(Passkeys::allowsLogin(request(), $inactive))->toBeFalse();
});

test('users are signed out on their next request once deactivated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
