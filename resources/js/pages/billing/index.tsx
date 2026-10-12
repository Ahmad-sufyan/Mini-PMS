import { Head } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import ModulePlaceholder from '@/components/module-placeholder';
import { index } from '@/routes/billing';

export default function Billing() {
    return (
        <>
            <Head title="Billing" />

            <ModulePlaceholder
                title="Billing"
                description="Create invoices and record patient payments"
                icon={Receipt}
            />
        </>
    );
}

Billing.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: index(),
        },
    ],
};
