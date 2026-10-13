import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import AppointmentFormFields from '@/components/appointment-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatDay } from '@/lib/dates';
import { edit, index } from '@/routes/appointments';
import type {
    Appointment,
    ClinicSchedule,
    Doctor,
    PatientSummary,
} from '@/types';

type Props = {
    appointment: Appointment;
    doctors: Doctor[];
    durations: number[];
    clinic: ClinicSchedule;
    patient: PatientSummary;
};

export default function EditAppointment({
    appointment,
    doctors,
    durations,
    clinic,
    patient,
}: Props) {
    const dayList = index({
        query: { from: appointment.date },
    });

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Appointments',
                href: index(),
            },
            {
                title: `${patient.full_name}, ${formatDay(appointment.date)}`,
                href: edit(appointment.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit appointment for ${patient.full_name}`} />

            <div className="px-4 py-6">
                <Heading
                    title="Reschedule appointment"
                    description={`Currently ${formatDay(appointment.date)}, ${appointment.start_time}–${appointment.end_time} with ${appointment.doctor.name}.`}
                />

                <Form
                    {...AppointmentController.update.form(appointment.id)}
                    disableWhileProcessing
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <AppointmentFormFields
                                doctors={doctors}
                                durations={durations}
                                clinic={clinic}
                                errors={errors}
                                patient={patient}
                                appointment={appointment}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-appointment-button"
                                >
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>

                                <Button variant="ghost" asChild>
                                    <Link href={dayList}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
