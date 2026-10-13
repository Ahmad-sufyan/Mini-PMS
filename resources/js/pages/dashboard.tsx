import { Head, usePage } from '@inertiajs/react';
import AdminDashboard from '@/components/dashboard/admin-dashboard';
import DoctorDashboard from '@/components/dashboard/doctor-dashboard';
import ReceptionDashboard from '@/components/dashboard/reception-dashboard';
import { Badge } from '@/components/ui/badge';
import { usePermissions } from '@/hooks/use-permissions';
import { formatOpeningHours } from '@/lib/clinic';
import { formatLongDay } from '@/lib/dates';
import { dashboard as dashboardRoute } from '@/routes';
import type { ClinicSchedule, Dashboard as DashboardData } from '@/types';

type Props = {
    clinic: ClinicSchedule & { name: string };
    today: string;
    dashboard: DashboardData;
};

function RoleDashboard({ dashboard }: { dashboard: DashboardData }) {
    switch (dashboard.role) {
        case 'admin':
            return <AdminDashboard dashboard={dashboard} />;
        case 'doctor':
            return <DoctorDashboard dashboard={dashboard} />;
        case 'reception':
            return <ReceptionDashboard dashboard={dashboard} />;
    }
}

export default function Dashboard({ clinic, today, dashboard }: Props) {
    const { auth } = usePage().props;
    const { role } = usePermissions();

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Welcome back, {auth.user.name}
                        </h1>
                        <Badge variant="outline">{role.label}</Badge>
                        {auth.user.specialization && (
                            <Badge variant="secondary">
                                {auth.user.specialization}
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {formatLongDay(today)} · {clinic.name} ·{' '}
                        {formatOpeningHours(clinic)}
                    </p>
                </div>

                <RoleDashboard dashboard={dashboard} />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboardRoute(),
        },
    ],
};
