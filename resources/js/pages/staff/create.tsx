import { Form, Head, Link } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import Heading from '@/components/heading';
import StaffFormFields from '@/components/staff-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/staff';
import type { RoleOption } from '@/types';

type Props = {
    roles: RoleOption[];
    passwordRules: string;
};

export default function CreateStaff({ roles, passwordRules }: Props) {
    return (
        <>
            <Head title="Add staff member" />

            <div className="px-4 py-6">
                <Heading
                    title="Add staff member"
                    description="Create an account for a new member of the clinic team. They can log in straight away."
                />

                <Form
                    {...StaffController.store.form()}
                    disableWhileProcessing
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <StaffFormFields
                                roles={roles}
                                errors={errors}
                                passwordRules={passwordRules}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="create-staff-button"
                                >
                                    {processing && <Spinner />}
                                    Create account
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

CreateStaff.layout = {
    breadcrumbs: [
        {
            title: 'Staff',
            href: index(),
        },
        {
            title: 'Add staff member',
            href: create(),
        },
    ],
};
