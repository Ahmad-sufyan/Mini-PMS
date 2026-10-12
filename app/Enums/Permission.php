<?php

namespace App\Enums;

/**
 * Every ability a staff member can be granted. Each case is registered as a
 * gate (see AppServiceProvider), so modules can authorize with
 * `Gate::authorize(Permission::ViewPatients)` or `can:patients.view`.
 */
enum Permission: string
{
    case ViewPatients = 'patients.view';
    case ManagePatients = 'patients.manage';
    case ViewAppointments = 'appointments.view';
    case ManageAppointments = 'appointments.manage';
    case ViewBilling = 'billing.view';
    case ManageBilling = 'billing.manage';
    case ManageStaff = 'staff.manage';
}
