import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import Heading from '@/components/heading';
import StaffFormFields from '@/components/staff-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { edit, index } from '@/routes/staff';
import type { RoleOption, StaffMember } from '@/types';

type Props = {
    staffMember: StaffMember;
    isSelf: boolean;
    roles: RoleOption[];
    passwordRules: string;
};

export default function EditStaff({
    staffMember,
    isSelf,
    roles,
    passwordRules,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Staff',
                href: index(),
            },
            {
                title: staffMember.name,
                href: edit(staffMember.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="px-4 py-6">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description="Update their details, role and account status"
                />

                <Form
                    {...StaffController.update.form(staffMember.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <StaffFormFields
                                roles={roles}
                                errors={errors}
                                passwordRules={passwordRules}
                                staffMember={staffMember}
                                isSelf={isSelf}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-staff-button"
                                >
                                    {processing && <Spinner />}
                                    Save changes
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
