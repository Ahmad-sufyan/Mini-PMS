<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    /**
     * Determine whether the user can see the visit's clinical details
     * (complaint, diagnosis, notes, vitals and prescriptions). Admins can
     * see every visit; doctors only the ones they recorded.
     */
    public function view(User $user, Visit $visit): bool
    {
        if (! $user->hasPermission(Permission::ViewClinicalRecords)) {
            return false;
        }

        return ! $user->hasPermission(Permission::RecordVisits) || $visit->doctor_id === $user->id;
    }

    /**
     * Determine whether the user can record a visit: only doctors, for their
     * own checked-in appointment or for a walk-in (no appointment).
     */
    public function create(User $user, ?Appointment $appointment = null): bool
    {
        if (! $user->hasPermission(Permission::RecordVisits)) {
            return false;
        }

        return $appointment === null
            || ($appointment->doctor_id === $user->id && $appointment->status === AppointmentStatus::CheckedIn);
    }

    /**
     * Determine whether the user can edit the visit: only the doctor who
     * recorded it.
     */
    public function update(User $user, Visit $visit): bool
    {
        return $user->hasPermission(Permission::RecordVisits) && $visit->doctor_id === $user->id;
    }

    /**
     * Determine whether the user can print the visit's prescription.
     */
    public function printPrescription(User $user, Visit $visit): bool
    {
        return $this->view($user, $visit);
    }
}
