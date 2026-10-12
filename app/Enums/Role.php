<?php

namespace App\Enums;

enum Role: string
{
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

    /**
     * Get the roles as value / label pairs for select inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
