import {
    CalendarDays,
    LayoutGrid,
    Receipt,
    UserCog,
    Users,
} from 'lucide-react';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import { index as appointments } from '@/routes/appointments';
import { index as billing } from '@/routes/billing';
import { index as patients } from '@/routes/patients';
import { index as staff } from '@/routes/staff';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Patients',
        href: patients(),
        icon: Users,
        permission: 'patients.view',
    },
    {
        title: 'Appointments',
        href: appointments(),
        icon: CalendarDays,
        permission: 'appointments.view',
    },
    {
        title: 'Billing',
        href: billing(),
        icon: Receipt,
        permission: 'billing.view',
    },
    {
        title: 'Staff',
        href: staff(),
        icon: UserCog,
        permission: 'staff.manage',
    },
];

/**
 * The main navigation, limited to the modules the current user can access.
 */
export function useMainNavItems(): NavItem[] {
    const { can } = usePermissions();

    return mainNavItems.filter(
        (item) => item.permission === undefined || can(item.permission),
    );
}
