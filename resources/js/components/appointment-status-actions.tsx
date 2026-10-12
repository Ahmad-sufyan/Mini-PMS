import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import AppointmentStatusController from '@/actions/App/Http/Controllers/AppointmentStatusController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { formatDay } from '@/lib/dates';
import type { Appointment, AppointmentStatus } from '@/types';

type NextStatus = Appointment['next_statuses'][number];

// Cancelling and marking a no-show can't be undone, so they ask first.
const confirmations: Partial<
    Record<AppointmentStatus, { title: string; action: string }>
> = {
    cancelled: {
        title: 'Cancel this appointment?',
        action: 'Cancel appointment',
    },
    no_show: {
        title: 'Mark this appointment as a no-show?',
        action: 'Mark as no-show',
    },
};

function StatusForm({
    appointment,
    status,
    children,
}: {
    appointment: Appointment;
    status: NextStatus;
    children: (processing: boolean) => ReactNode;
}) {
    return (
        <Form
            {...AppointmentStatusController.form({
                appointment: appointment.id,
                status: status.value,
            })}
            options={{ preserveScroll: true, preserveState: true }}
            onError={(errors) => {
                if (errors.status) {
                    toast.error(errors.status);
                }
            }}
        >
            {({ processing }) => children(processing)}
        </Form>
    );
}

function ConfirmStatusDialog({
    appointment,
    status,
    confirmation,
}: {
    appointment: Appointment;
    status: NextStatus;
    confirmation: { title: string; action: string };
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    data-test={`appointment-${status.value}-button`}
                >
                    {status.label}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{confirmation.title}</DialogTitle>
                <DialogDescription>
                    {appointment.patient.full_name} with{' '}
                    {appointment.doctor.name} on {formatDay(appointment.date)}{' '}
                    at {appointment.start_time}. This can't be undone.
                </DialogDescription>

                <StatusForm appointment={appointment} status={status}>
                    {(processing) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Keep as it is
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                disabled={processing}
                                data-test={`confirm-appointment-${status.value}-button`}
                            >
                                {confirmation.action}
                            </Button>
                        </DialogFooter>
                    )}
                </StatusForm>
            </DialogContent>
        </Dialog>
    );
}

/**
 * The status buttons (check in, complete, cancel, no-show) the current user
 * can use on the appointment.
 */
export default function AppointmentStatusActions({
    appointment,
}: {
    appointment: Appointment;
}) {
    if (appointment.next_statuses.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {appointment.next_statuses.map((status) => {
                const confirmation = confirmations[status.value];

                if (confirmation) {
                    return (
                        <ConfirmStatusDialog
                            key={status.value}
                            appointment={appointment}
                            status={status}
                            confirmation={confirmation}
                        />
                    );
                }

                return (
                    <StatusForm
                        key={status.value}
                        appointment={appointment}
                        status={status}
                    >
                        {(processing) => (
                            <Button
                                size="sm"
                                disabled={processing}
                                data-test={`appointment-${status.value}-button`}
                            >
                                {status.label}
                            </Button>
                        )}
                    </StatusForm>
                );
            })}
        </div>
    );
}
