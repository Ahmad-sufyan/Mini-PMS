import { Banknote, CalendarDays, CircleAlert, Users } from 'lucide-react';
import {
    AppointmentsByStatusChart,
    AppointmentsPerDayChart,
    RevenuePerMonthChart,
} from '@/components/dashboard/charts';
import StatCard from '@/components/dashboard/stat-card';
import { useCurrency } from '@/hooks/use-currency';
import { index as appointments } from '@/routes/appointments';
import { index as invoices } from '@/routes/invoices';
import { index as patients } from '@/routes/patients';
import type { AdminDashboard as Data } from '@/types';

export default function AdminDashboard({ dashboard }: { dashboard: Data }) {
    const { format } = useCurrency();
    const { stats } = dashboard;

    return (
        <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Patients"
                    value={stats.patients}
                    hint="Active patient records"
                    icon={Users}
                    href={patients()}
                />
                <StatCard
                    label="Today's appointments"
                    value={stats.appointments_today}
                    hint={`${stats.completed_today} completed so far`}
                    icon={CalendarDays}
                    href={appointments()}
                />
                <StatCard
                    label="Revenue this month"
                    value={format(stats.revenue_this_month)}
                    hint="Payments received"
                    icon={Banknote}
                    href={invoices()}
                />
                <StatCard
                    label="Outstanding"
                    value={format(stats.outstanding)}
                    hint={`Across ${stats.outstanding_invoices} ${stats.outstanding_invoices === 1 ? 'invoice' : 'invoices'}`}
                    icon={CircleAlert}
                    href={invoices()}
                />
            </div>

            <AppointmentsPerDayChart data={dashboard.appointmentsPerDay} />

            <div className="grid gap-4 lg:grid-cols-2">
                <RevenuePerMonthChart data={dashboard.revenuePerMonth} />
                <AppointmentsByStatusChart
                    data={dashboard.appointmentsByStatus}
                />
            </div>
        </div>
    );
}
