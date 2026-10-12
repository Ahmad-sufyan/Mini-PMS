import { Form, Head, Link } from '@inertiajs/react';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import Heading from '@/components/heading';
import PatientFormFields from '@/components/patient-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/patients';
import type { BloodGroup, Gender, SelectOption } from '@/types';

type Props = {
    genders: SelectOption<Gender>[];
    bloodGroups: SelectOption<BloodGroup>[];
};

export default function CreatePatient({ genders, bloodGroups }: Props) {
    return (
        <>
            <Head title="Register patient" />

            <div className="px-4 py-6">
                <Heading
                    title="Register patient"
                    description="A medical record number (MRN) is assigned automatically once the patient is saved."
                />

                <Form
                    {...PatientController.store.form()}
                    disableWhileProcessing
                    className="max-w-2xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <PatientFormFields
                                genders={genders}
                                bloodGroups={bloodGroups}
                                errors={errors}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="create-patient-button"
                                >
                                    {processing && <Spinner />}
                                    Register patient
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

CreatePatient.layout = {
    breadcrumbs: [
        {
            title: 'Patients',
            href: index(),
        },
        {
            title: 'Register patient',
            href: create(),
        },
    ],
};
