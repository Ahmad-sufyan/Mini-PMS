import { usePage } from '@inertiajs/react';
import type { Permission, RoleOption } from '@/types';

export type UsePermissionsReturn = {
    role: RoleOption;
    permissions: Permission[];
    can: (permission: Permission) => boolean;
};

export function usePermissions(): UsePermissionsReturn {
    const { auth } = usePage().props;

    const can = (permission: Permission): boolean =>
        auth.permissions.includes(permission);

    return {
        role: auth.role,
        permissions: auth.permissions,
        can,
    };
}
