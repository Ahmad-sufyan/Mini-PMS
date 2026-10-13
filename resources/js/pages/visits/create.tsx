import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import VisitController from '@/actions/App/Http/Controllers/VisitController';
import InputError from '@/components/input-error';
import VisitFormFields from '@/components/visit-form-fields';
import VisitPatientBanner from '@/components/visit-patient-banner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { addDays } from '@/lib/dates';
import { index as patientsIndex, show } from '@/routes/patients';
import type { Appointment, VisitPatient } from '@/types';

type Props = {
    patient: VisitPatient;
    appointment: Appointment | null;
    today: string;
    frequencies: string[];
    durations: string[];
};

export default function CreateVisit({
    patient,
    appointment,
    today,
    frequencies,
    durations,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Patients',
                href: patientsIndex(),
            },
            {
                title: patient.full_name,
                href: show(patient.id),
            },
            {
                title: 'New visit',
                href: show(patient.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Visit: ${patient.full_name}`} />

            <div className="space-y-6 px-4 py-6">
                <VisitPatientBanner
                    patient={patient}
                    appointment={appointment}
                />

                <Form
                    {...VisitController.store.form()}
                    disableWhileProcessing
                    className="max-w-4xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            {appointment ? (
                                <input
                                    type="hidden"
                                    name="appointment_id"
                                    value={appointment.id}
                                />
                            ) : (
                                <input
                                    type="hidden"
                                    name="patient_id"
                                    value={patient.id}
                                />
                            )}

                            <VisitFormFields
                                defaultComplaint={appointment?.reason}
                                minFollowUp={addDays(today, 1)}
                                errors={errors}
                                frequencies={frequencies}
                                durations={durations}
                            />

                            <InputError
                                message={
                                    errors.appointment_id ?? errors.patient_id
                                }
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-visit-button"
                                >
                                    {processing && <Spinner />}
                                    {appointment
                                        ? 'Save visit and complete appointment'
                                        : 'Save visit'}
                                </Button>

                                <Button variant="ghost" asChild>
                                    <Link href={show(patient.id)}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
