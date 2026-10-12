import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import Heading from '@/components/heading';
import PatientFormFields from '@/components/patient-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index, show } from '@/routes/patients';
import type { BloodGroup, Gender, Patient, SelectOption } from '@/types';

type Props = {
    patient: Patient;
    genders: SelectOption<Gender>[];
    bloodGroups: SelectOption<BloodGroup>[];
};

export default function EditPatient({ patient, genders, bloodGroups }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Patients',
                href: index(),
            },
            {
                title: patient.full_name,
                href: show(patient.id),
            },
            {
                title: 'Edit',
                href: edit(patient.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${patient.full_name}`} />

            <div className="px-4 py-6">
                <Heading
                    title={`Edit ${patient.full_name}`}
                    description={`Medical record number ${patient.mrn}`}
                />

                <Form
                    {...PatientController.update.form(patient.id)}
                    disableWhileProcessing
                    className="max-w-2xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <PatientFormFields
                                genders={genders}
                                bloodGroups={bloodGroups}
                                errors={errors}
                                patient={patient}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-patient-button"
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
