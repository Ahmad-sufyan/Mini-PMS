import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/heading';
import InvoiceFormFields from '@/components/invoice-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index, show } from '@/routes/invoices';
import type { Invoice, Service } from '@/types';

type Props = {
    invoice: Invoice;
    services: Service[];
    today: string;
};

export default function EditInvoice({ invoice, services, today }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Billing',
                href: index(),
            },
            {
                title: invoice.invoice_number,
                href: show(invoice.id),
            },
            {
                title: 'Edit',
                href: show(invoice.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${invoice.invoice_number}`} />

            <div className="px-4 py-6">
                <Heading
                    title={`Edit ${invoice.invoice_number}`}
                    description="Invoices can only be changed until a payment is recorded."
                />

                <Form
                    {...InvoiceController.update.form(invoice.id)}
                    disableWhileProcessing
                    className="max-w-4xl space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <InvoiceFormFields
                                services={services}
                                errors={errors}
                                items={invoice.items ?? []}
                                discount={invoice.discount}
                                issuedOn={invoice.issued_on}
                                maxDate={today}
                                notes={invoice.notes}
                                patient={invoice.patient}
                            />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-invoice-button"
                                >
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>

                                <Button variant="ghost" asChild>
                                    <Link href={show(invoice.id)}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
