<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

test('the registration feature is disabled', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();
});

test('the registration routes are not registered', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('register.store'))->toBeFalse();
});

test('the registration screen cannot be opened', function () {
    $this->get('/register')->assertNotFound();
});

test('new users cannot register themselves', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
