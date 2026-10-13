import { Link } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus } from 'lucide-react';
import AppointmentStatusActions from '@/components/appointment-status-actions';
import AppointmentStatusBadge from '@/components/appointment-status-badge';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDay } from '@/lib/dates';
import { create, edit } from '@/routes/appointments';
import type { Appointment, Patient } from '@/types';

function AppointmentSection({
    title,
    appointments,
    empty,
}: {
    title: string;
    appointments: Appointment[];
    empty: string;
}) {
    return (
        <section className="space-y-2">
            <h3 className="text-sm font-medium">
                {title}{' '}
                <span className="font-normal text-muted-foreground">
                    ({appointments.length})
                </span>
            </h3>

            {appointments.length === 0 ? (
                <p className="text-sm text-muted-foreground">{empty}</p>
            ) : (
                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">When</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Doctor
                                </TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    Reason
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="pr-4">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {appointments.map((appointment) => (
                                <TableRow key={appointment.id}>
                                    <TableCell className="pl-4">
                                        <div className="font-medium">
                                            {formatDay(appointment.date)}
                                        </div>
                                        <div className="text-xs text-muted-foreground tabular-nums">
                                            {appointment.start_time}–
                                            {appointment.end_time}
                                        </div>
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {appointment.doctor.name}
                                    </TableCell>
                                    <TableCell className="hidden max-w-56 truncate lg:table-cell">
                                        {appointment.reason}
                                    </TableCell>
                                    <TableCell>
                                        <AppointmentStatusBadge
                                            status={appointment.status}
                                            label={appointment.status_label}
                                        />
                                    </TableCell>
                                    <TableCell className="pr-4">
                                        <div className="flex items-center justify-end gap-2">
                                            <AppointmentStatusActions
                                                appointment={appointment}
                                            />
                                            {appointment.can_edit && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(
                                                            appointment.id,
                                                        )}
                                                        aria-label="Edit appointment"
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </section>
    );
}

/**
 * The Appointments tab of the patient page. Doctors only see their own
 * appointments with the patient.
 */
export default function PatientAppointments({
    patient,
    appointments,
    today,
}: {
    patient: Patient;
    appointments: Appointment[];
    today: string;
}) {
    const { can } = usePermissions();
    const canBook = can('appointments.manage') && patient.deleted_at === null;

    const isUpcoming = (appointment: Appointment) =>
        appointment.date >= today &&
        (appointment.status === 'scheduled' ||
            appointment.status === 'checked_in');

    // Appointments arrive newest first; show the next one first.
    const upcoming = appointments.filter(isUpcoming).reverse();
    const history = appointments.filter(
        (appointment) => !isUpcoming(appointment),
    );

    const bookButton = canBook && (
        <Button asChild>
            <Link
                href={create({ query: { patient: patient.id } })}
                data-test="book-patient-appointment-button"
            >
                <Plus />
                Book appointment
            </Link>
        </Button>
    );

    if (appointments.length === 0) {
        return (
            <EmptyState
                icon={CalendarDays}
                title="No appointments yet"
                description="Appointments booked for this patient will appear here."
            >
                {bookButton}
            </EmptyState>
        );
    }

    return (
        <div className="space-y-6">
            {canBook && <div className="flex justify-end">{bookButton}</div>}

            <AppointmentSection
                title="Upcoming"
                appointments={upcoming}
                empty="No upcoming appointments."
            />
            <AppointmentSection
                title="History"
                appointments={history}
                empty="No past appointments."
            />
        </div>
    );
}
