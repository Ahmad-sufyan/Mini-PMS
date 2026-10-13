<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can list appointments (doctors only get
     * their own, see Appointment::visibleTo()).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewAppointments);
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission(Permission::ManageAppointments)
            || ($user->hasPermission(Permission::ViewAppointments) && $appointment->doctor_id === $user->id);
    }

    /**
     * Determine whether the user can book appointments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAppointments);
    }

    /**
     * Determine whether the user can reschedule or edit the appointment.
     * Once a patient has checked in, the booking can't be changed.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission(Permission::ManageAppointments)
            && $appointment->status === AppointmentStatus::Scheduled;
    }

    /**
     * Determine whether the user can move the appointment to the given
     * status. Whether that status change is valid is checked separately.
     */
    public function changeStatus(User $user, Appointment $appointment, AppointmentStatus $status): bool
    {
        if ($user->hasPermission(Permission::ManageAppointments)) {
            return true;
        }

        // Doctors can check in and complete their own appointments.
        return $this->view($user, $appointment)
            && in_array($status, [AppointmentStatus::CheckedIn, AppointmentStatus::Completed], true);
    }
}
