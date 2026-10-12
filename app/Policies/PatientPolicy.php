<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view the patient list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewPatients);
    }

    /**
     * Determine whether the user can view the patient. Deleted records are
     * only visible to the staff who can restore them.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $patient->trashed()
            ? $this->restore($user, $patient)
            : $user->hasPermission(Permission::ViewPatients);
    }

    /**
     * Determine whether the user can register patients.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePatients);
    }

    /**
     * Determine whether the user can update the patient.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::ManagePatients) && ! $patient->trashed();
    }

    /**
     * Determine whether the user can (soft) delete the patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::DeletePatients) && ! $patient->trashed();
    }

    /**
     * Determine whether the user can restore the deleted patient.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasPermission(Permission::DeletePatients) && $patient->trashed();
    }

    /**
     * Patient records are never permanently deleted.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}
