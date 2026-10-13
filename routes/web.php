<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VisitController;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::controller(PatientController::class)->prefix('patients')->name('patients.')->group(function () {
        Route::get('/', 'index')->can('viewAny', Patient::class)->name('index');
        Route::get('create', 'create')->can('create', Patient::class)->name('create');
        Route::post('/', 'store')->can('create', Patient::class)->name('store');
        Route::get('search', 'search')->can('viewAny', Patient::class)->name('search');
        Route::get('{patient}', 'show')->withTrashed()->can('view', 'patient')->name('show');
        Route::get('{patient}/edit', 'edit')->can('update', 'patient')->name('edit');
        Route::put('{patient}', 'update')->can('update', 'patient')->name('update');
        Route::delete('{patient}', 'destroy')->can('delete', 'patient')->name('destroy');
        Route::patch('{patient}/restore', 'restore')->withTrashed()->can('restore', 'patient')->name('restore');
    });

    Route::controller(AppointmentController::class)->prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', 'index')->can('viewAny', Appointment::class)->name('index');
        Route::get('week', 'week')->can('viewAny', Appointment::class)->name('week');
        Route::get('create', 'create')->can('create', Appointment::class)->name('create');
        Route::post('/', 'store')->can('create', Appointment::class)->name('store');
        Route::get('{appointment}/edit', 'edit')->can('update', 'appointment')->name('edit');
        Route::put('{appointment}', 'update')->can('update', 'appointment')->name('update');
    });

    Route::patch('appointments/{appointment}/status/{status}', AppointmentStatusController::class)
        ->can('changeStatus', ['appointment', 'status'])
        ->name('appointments.status');

    // Creating a visit is authorized in the controller and form request, as
    // it depends on the appointment being visited.
    Route::controller(VisitController::class)->prefix('visits')->name('visits.')->group(function () {
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{visit}/edit', 'edit')->can('update', 'visit')->name('edit');
        Route::put('{visit}', 'update')->can('update', 'visit')->name('update');
        Route::get('{visit}/prescription', 'prescription')->can('printPrescription', 'visit')->name('prescription');
    });

    Route::controller(InvoiceController::class)->prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', 'index')->can('viewAny', Invoice::class)->name('index');
        Route::get('create', 'create')->can('create', Invoice::class)->name('create');
        Route::post('/', 'store')->can('create', Invoice::class)->name('store');
        Route::get('{invoice}', 'show')->can('view', 'invoice')->name('show');
        Route::get('{invoice}/edit', 'edit')->can('update', 'invoice')->name('edit');
        Route::put('{invoice}', 'update')->can('update', 'invoice')->name('update');
        Route::get('{invoice}/print', 'print')->can('view', 'invoice')->name('print');
        Route::patch('{invoice}/void', 'void')->can('void', 'invoice')->name('void');
    });

    Route::post('invoices/{invoice}/payments', InvoicePaymentController::class)
        ->can('recordPayment', 'invoice')
        ->name('invoices.payments.store');

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
