import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import AppointmentStatusActions from '@/components/appointment-status-actions';
import AppointmentStatusBadge from '@/components/appointment-status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatLongDay } from '@/lib/dates';
import { edit } from '@/routes/appointments';
import { show } from '@/routes/patients';
import type { Appointment } from '@/types';

export default function AppointmentDetailsDialog({
    appointment,
    onClose,
}: {
    appointment: Appointment | null;
    onClose: () => void;
}) {
    return (
        <Dialog
            open={appointment !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                {appointment && (
                    <>
                        <div className="space-y-1">
                            <DialogTitle>
                                <Link
                                    href={show(appointment.patient.id)}
                                    className="hover:underline"
                                >
                                    {appointment.patient.full_name}
                                </Link>
                            </DialogTitle>
                            <DialogDescription>
                                {appointment.patient.mrn}
                            </DialogDescription>
                        </div>

                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">When</dt>
                            <dd>
                                {formatLongDay(appointment.date)},{' '}
                                {appointment.start_time}–{appointment.end_time}
                            </dd>
                            <dt className="text-muted-foreground">Doctor</dt>
                            <dd>
                                {appointment.doctor.name}
                                {appointment.doctor.specialization &&
                                    ` · ${appointment.doctor.specialization}`}
                            </dd>
                            <dt className="text-muted-foreground">Reason</dt>
                            <dd>{appointment.reason}</dd>
                            {appointment.notes && (
                                <>
                                    <dt className="text-muted-foreground">
                                        Notes
                                    </dt>
                                    <dd className="whitespace-pre-line">
                                        {appointment.notes}
                                    </dd>
                                </>
                            )}
                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                <AppointmentStatusBadge
                                    status={appointment.status}
                                    label={appointment.status_label}
                                />
                            </dd>
                        </dl>

                        <div className="flex flex-wrap items-center justify-end gap-2">
                            {appointment.can_edit && (
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={edit(appointment.id)}>
                                        <Pencil />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                            <AppointmentStatusActions
                                appointment={appointment}
                            />
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
