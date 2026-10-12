<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Role: string
{
    use HasOptions;

    case Admin = 'admin';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';

    /**
     * Get the human readable name of the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Doctor => 'Doctor',
            self::Receptionist => 'Receptionist',
        };
    }

    /**
     * Get the permissions granted to the role.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Doctor => [
                Permission::ViewPatients,
                Permission::ViewAppointments,
            ],
            self::Receptionist => [
                Permission::ViewPatients,
                Permission::ManagePatients,
                Permission::ViewAppointments,
                Permission::ManageAppointments,
                Permission::ViewBilling,
                Permission::ManageBilling,
            ],
        };
    }

    /**
     * Determine if the role has been granted the given permission.
     */
    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
