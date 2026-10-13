<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\DevTools\DevTools;

test('a reset link request for an unknown email looks the same as for a staff member', function () {
    Notification::fake();
    User::factory()->create(['email' => 'rita@example.com']);

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('passwords.sent'));

    Notification::assertNothingSent();
});

test('a malformed login request is turned away, not an error page', function () {
    $this->post(route('login.store'), ['email' => ['rita@example.com'], 'password' => 'password'])
        ->assertRedirect()
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('passkey attempts are limited by session and IP address, not by the credential sent', function () {
    $keys = function (string $credential): array {
        $request = Request::create('/passkeys/login', 'POST', ['credential' => ['id' => $credential]]);
        $request->setLaravelSession(app('session.store'));

        return array_map(fn ($limit) => $limit->key, RateLimiter::limiter('passkeys')($request));
    };

    expect($keys('first-credential'))->toBe($keys('another-credential'));
});

test('inertia devtools stay off unless they are switched on, even locally', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(DevTools::enabled())->toBeFalse();
});
