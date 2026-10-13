import { Link } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import AppointmentStatusActions from '@/components/appointment-status-actions';
import AppointmentStatusBadge from '@/components/appointment-status-badge';
import EmptyState from '@/components/empty-state';
import { cn } from '@/lib/utils';
import { show } from '@/routes/patients';
import type { Appointment } from '@/types';

/**
 * Today's appointments as a list that works on any screen size, with the
 * status buttons (check in, start visit, ...) the user can use.
 */
export default function TodaysAppointments({
    appointments,
    showDoctor = false,
    highlightId = null,
    emptyDescription,
}: {
    appointments: Appointment[];
    showDoctor?: boolean;
    highlightId?: number | null;
    emptyDescription: string;
}) {
    if (appointments.length === 0) {
        return (
            <EmptyState
                icon={CalendarDays}
                title="No appointments today"
                description={emptyDescription}
            />
        );
    }

    return (
        <ul className="divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            {appointments.map((appointment) => {
                const inactive =
                    appointment.status === 'cancelled' ||
                    appointment.status === 'no_show';

                return (
                    <li
                        key={appointment.id}
                        className={cn(
                            'flex flex-col gap-3 p-4 sm:flex-row sm:items-center',
                            appointment.id === highlightId &&
                                'bg-primary/5 ring-2 ring-primary ring-inset',
                            inactive && 'text-muted-foreground',
                        )}
                        data-test={
                            appointment.id === highlightId
                                ? 'next-appointment'
                                : 'appointment-row'
                        }
                    >
                        <div className="w-28 shrink-0 font-medium tabular-nums">
                            {appointment.start_time}–{appointment.end_time}
                        </div>
                        <div className="min-w-0 flex-1">
                            <Link
                                href={show(appointment.patient.id)}
                                className={cn(
                                    'font-medium hover:underline',
                                    inactive && 'line-through',
                                )}
                            >
                                {appointment.patient.full_name}
                            </Link>
                            <div className="truncate text-sm text-muted-foreground">
                                {showDoctor && `${appointment.doctor.name} · `}
                                {appointment.reason}
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                            <AppointmentStatusBadge
                                status={appointment.status}
                                label={appointment.status_label}
                            />
                            <AppointmentStatusActions
                                appointment={appointment}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
