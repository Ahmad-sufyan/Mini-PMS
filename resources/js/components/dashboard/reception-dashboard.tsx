import { Link } from '@inertiajs/react';
import {
    CalendarCheck,
    CalendarPlus,
    Clock,
    Receipt,
    UserCheck,
    UserPlus,
} from 'lucide-react';
import StatCard from '@/components/dashboard/stat-card';
import TodaysAppointments from '@/components/dashboard/todays-appointments';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create as createAppointment } from '@/routes/appointments';
import { index as invoices } from '@/routes/invoices';
import { create as createPatient } from '@/routes/patients';
import type { ReceptionDashboard as Data } from '@/types';

export default function ReceptionDashboard({ dashboard }: { dashboard: Data }) {
    const { stats } = dashboard;

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap gap-2">
                <Button asChild>
                    <Link href={createPatient()} data-test="quick-new-patient">
                        <UserPlus />
                        New patient
                    </Link>
                </Button>
                <Button variant="outline" asChild>
                    <Link
                        href={createAppointment()}
                        data-test="quick-new-appointment"
                    >
                        <CalendarPlus />
                        New appointment
                    </Link>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Still to arrive"
                    value={stats.to_come}
                    hint={`Out of ${stats.total} appointments today`}
                    icon={Clock}
                />
                <StatCard
                    label="Waiting"
                    value={stats.waiting}
                    hint="Checked in, waiting for the doctor"
                    icon={UserCheck}
                />
                <StatCard
                    label="Seen today"
                    value={stats.completed}
                    hint="Completed visits"
                    icon={CalendarCheck}
                />
                <StatCard
                    label="Unpaid invoices"
                    value={dashboard.unpaidInvoices}
                    hint="Unpaid or partly paid"
                    icon={Receipt}
                    href={invoices()}
                />
            </div>

            <section className="space-y-3">
                <Heading
                    variant="small"
                    title="Today's appointments"
                    description="Check patients in as they arrive"
                />
                <TodaysAppointments
                    appointments={dashboard.appointments}
                    showDoctor
                    emptyDescription="Nothing is booked for today yet."
                />
            </section>
        </div>
    );
}
