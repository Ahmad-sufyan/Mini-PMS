import { Form, Head, Link } from '@inertiajs/react';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import AppointmentFormFields from '@/components/appointment-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/appointments';
import type { ClinicSchedule, Doctor, PatientSummary } from '@/types';

type Props = {
    doctors: Doctor[];
    durations: number[];
    clinic: ClinicSchedule;
    patient: PatientSummary | null;
    defaultDate: string;
};

export default function CreateAppointment({
    doctors,
    durations,
    clinic,
    patient,
    defaultDate,
}: Props) {
    return (
        <>
            <Head title="Book appointment" />

            <div className="px-4 py-6">
                <Heading
                    title="Book appointment"
                    description="Doctors can't be double-booked, and appointments must fit within clinic hours."
                />

                <Form
                    {...AppointmentController.store.form()}
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
                                defaultDate={defaultDate}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="create-appointment-button"
                                >
                                    {processing && <Spinner />}
                                    Book appointment
                                </Button>

                                <Button variant="ghost" asChild>
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateAppointment.layout = {
    breadcrumbs: [
        {
            title: 'Appointments',
            href: index(),
        },
        {
            title: 'Book appointment',
            href: create(),
        },
    ],
};
