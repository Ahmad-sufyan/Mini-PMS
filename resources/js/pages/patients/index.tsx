import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import ModulePlaceholder from '@/components/module-placeholder';
import { index } from '@/routes/patients';

export default function Patients() {
    return (
        <>
            <Head title="Patients" />

            <ModulePlaceholder
                title="Patients"
                description="Register patients and keep their records up to date"
                icon={Users}
            />
        </>
    );
}

Patients.layout = {
    breadcrumbs: [
        {
            title: 'Patients',
            href: index(),
        },
    ],
};
