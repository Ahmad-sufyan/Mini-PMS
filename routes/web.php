<?php

use App\Enums\Permission;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StaffController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::inertia('patients', 'patients/index')
        ->can(Permission::ViewPatients)
        ->name('patients.index');

    Route::inertia('appointments', 'appointments/index')
        ->can(Permission::ViewAppointments)
        ->name('appointments.index');

    Route::inertia('billing', 'billing/index')
        ->can(Permission::ViewBilling)
        ->name('billing.index');

    Route::controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', 'index')->can('viewAny', User::class)->name('index');
        Route::get('create', 'create')->can('create', User::class)->name('create');
        Route::post('/', 'store')->can('create', User::class)->name('store');
        Route::get('{user}/edit', 'edit')->can('update', 'user')->name('edit');
        Route::put('{user}', 'update')->can('update', 'user')->name('update');
        Route::patch('{user}/deactivate', 'deactivate')->can('deactivate', 'user')->name('deactivate');
        Route::patch('{user}/activate', 'activate')->can('activate', 'user')->name('activate');
    });
});

require __DIR__.'/settings.php';
