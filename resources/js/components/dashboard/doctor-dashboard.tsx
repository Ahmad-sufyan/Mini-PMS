import { Link } from '@inertiajs/react';
import { CalendarCheck, Clock, UserCheck } from 'lucide-react';
import AppointmentStatusActions from '@/components/appointment-status-actions';
import AppointmentStatusBadge from '@/components/appointment-status-badge';
import StatCard from '@/components/dashboard/stat-card';
import TodaysAppointments from '@/components/dashboard/todays-appointments';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/patients';
import type { DoctorDashboard as Data } from '@/types';

export default function DoctorDashboard({ dashboard }: { dashboard: Data }) {
    const { stats, appointments } = dashboard;
    const next = appointments.find(
        (appointment) => appointment.id === dashboard.nextAppointmentId,
    );

    return (
        <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Waiting"
                    value={stats.waiting}
                    hint="Checked in and ready to see"
                    icon={UserCheck}
                />
                <StatCard
                    label="Still to come"
                    value={stats.to_come}
                    hint="Scheduled for later today"
                    icon={Clock}
                />
                <StatCard
                    label="Seen today"
                    value={stats.completed}
                    hint={`Out of ${stats.total} appointments`}
                    icon={CalendarCheck}
                />
            </div>

            {next && (
                <Card
                    className="gap-0 border-primary py-4"
                    data-test="next-patient-card"
                >
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">
                                {next.status === 'checked_in'
                                    ? 'Waiting now'
                                    : `Next patient at ${next.start_time}`}
                            </p>
                            <p className="text-lg font-semibold">
                                <Link
                                    href={show(next.patient.id)}
                                    className="hover:underline"
                                >
                                    {next.patient.full_name}
                                </Link>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {next.start_time}–{next.end_time} ·{' '}
                                {next.reason}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <AppointmentStatusBadge
                                status={next.status}
                                label={next.status_label}
                            />
                            <AppointmentStatusActions appointment={next} />
                        </div>
                    </CardContent>
                </Card>
            )}

            <section className="space-y-3">
                <Heading
                    variant="small"
                    title="Today's schedule"
                    description="Check patients in as they arrive, then start the visit"
                />
                <TodaysAppointments
                    appointments={appointments}
                    highlightId={dashboard.nextAppointmentId}
                    emptyDescription="You have no appointments booked for today."
                />
            </section>
        </div>
    );
}
