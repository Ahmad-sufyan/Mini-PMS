import { Head } from '@inertiajs/react';
import { CalendarDays } from 'lucide-react';
import ModulePlaceholder from '@/components/module-placeholder';
import { index } from '@/routes/appointments';

export default function Appointments() {
    return (
        <>
            <Head title="Appointments" />

            <ModulePlaceholder
                title="Appointments"
                description="Book, reschedule and track patient appointments"
                icon={CalendarDays}
            />
        </>
    );
}

Appointments.layout = {
    breadcrumbs: [
        {
            title: 'Appointments',
            href: index(),
        },
    ],
};
