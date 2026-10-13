import { Form, Head, Link } from '@inertiajs/react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import InvoiceFormFields from '@/components/invoice-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/invoices';
import type {
    InvoiceItem,
    InvoiceVisit,
    PatientSummary,
    Service,
} from '@/types';

type Props = {
    services: Service[];
    patient: PatientSummary | null;
    visit: InvoiceVisit | null;
    items: InvoiceItem[];
    defaultDate: string;
};

export default function CreateInvoice({
    services,
    patient,
    visit,
    items,
    defaultDate,
}: Props) {
    return (
        <>
            <Head title="New invoice" />

            <div className="px-4 py-6">
                <Heading
                    title="New invoice"
                    description={
                        visit
                            ? 'The visit is already on the invoice. Add anything else that was done.'
                            : 'The invoice number is assigned when it is saved.'
                    }
                />

                <Form
                    {...InvoiceController.store.form()}
                    disableWhileProcessing
                    className="max-w-4xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <InvoiceFormFields
                                services={services}
                                errors={errors}
                                items={items}
                                issuedOn={defaultDate}
                                maxDate={defaultDate}
                                patient={patient}
                                choosePatient={visit === null}
                                visit={visit}
                            />

                            <InputError message={errors.visit_id} />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="create-invoice-button"
                                >
                                    {processing && <Spinner />}
                                    Create invoice
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

CreateInvoice.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: index(),
        },
        {
            title: 'New invoice',
            href: create(),
        },
    ],
};
