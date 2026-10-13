import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import VisitController from '@/actions/App/Http/Controllers/VisitController';
import VisitFormFields from '@/components/visit-form-fields';
import VisitPatientBanner from '@/components/visit-patient-banner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { addDays, formatDay } from '@/lib/dates';
import { edit } from '@/routes/visits';
import { index as patientsIndex, show } from '@/routes/patients';
import type { ClinicalVisit, VisitPatient } from '@/types';

type Props = {
    visit: ClinicalVisit;
    patient: VisitPatient;
    frequencies: string[];
    durations: string[];
};

export default function EditVisit({
    visit,
    patient,
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
                title: `Visit on ${formatDay(visit.date)}`,
                href: edit(visit.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit visit: ${patient.full_name}`} />

            <div className="space-y-6 px-4 py-6">
                <VisitPatientBanner patient={patient} />

                <p className="text-sm text-muted-foreground">
                    {`Editing the visit on ${formatDay(visit.date)} at ${visit.time}.`}
                </p>

                <Form
                    {...VisitController.update.form(visit.id)}
                    disableWhileProcessing
                    className="max-w-4xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <VisitFormFields
                                visit={visit}
                                minFollowUp={addDays(visit.date, 1)}
                                errors={errors}
                                frequencies={frequencies}
                                durations={durations}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-visit-button"
                                >
                                    {processing && <Spinner />}
                                    Save changes
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
