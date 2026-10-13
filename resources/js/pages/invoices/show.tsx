import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Ban, Pencil, Printer } from 'lucide-react';
import InvoiceStatusBadge from '@/components/invoice-status-badge';
import RecordPaymentDialog from '@/components/record-payment-dialog';
import VoidInvoiceDialog from '@/components/void-invoice-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCurrency } from '@/hooks/use-currency';
import { formatDate, formatDay } from '@/lib/dates';
import { edit, index, print, show } from '@/routes/invoices';
import { show as showPatient } from '@/routes/patients';
import type {
    Invoice,
    InvoiceVisit,
    PaymentMethod,
    SelectOption,
} from '@/types';

type Props = {
    invoice: Invoice;
    visit: InvoiceVisit | null;
    paymentMethods: SelectOption<PaymentMethod>[];
    today: string;
};

export default function ShowInvoice({
    invoice,
    visit,
    paymentMethods,
    today,
}: Props) {
    const { format } = useCurrency();
    const items = invoice.items ?? [];
    const payments = invoice.payments ?? [];

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
        ],
    });

    return (
        <>
            <Head title={invoice.invoice_number} />

            <div className="flex max-w-5xl flex-col gap-6 px-4 py-6">
                {invoice.status === 'void' && (
                    <Alert variant="destructive">
                        <Ban />
                        <AlertTitle>
                            This invoice was voided
                            {invoice.voided_on &&
                                ` on ${formatDate(invoice.voided_on)}`}
                        </AlertTitle>
                        <AlertDescription>
                            It doesn't count towards billing or the patient's
                            balance.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="font-mono text-xl font-semibold tracking-tight">
                                {invoice.invoice_number}
                            </h1>
                            <InvoiceStatusBadge
                                status={invoice.status}
                                label={invoice.status_label}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Issued {formatDate(invoice.issued_on)} to{' '}
                            <Link
                                href={showPatient(invoice.patient.id, {
                                    query: { tab: 'billing' },
                                })}
                                className="font-medium text-foreground hover:underline"
                            >
                                {invoice.patient.full_name}
                            </Link>{' '}
                            ({invoice.patient.mrn})
                            {visit &&
                                ` for the visit with ${visit.doctor} on ${formatDay(visit.date)}`}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {invoice.can.record_payment && (
                            <RecordPaymentDialog
                                invoice={invoice}
                                methods={paymentMethods}
                                today={today}
                            />
                        )}
                        {invoice.can.update && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={edit(invoice.id)}
                                    data-test="edit-invoice-button"
                                >
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <a
                                href={print.url(invoice.id)}
                                target="_blank"
                                rel="noopener"
                                data-test="print-invoice-link"
                            >
                                <Printer />
                                Print
                            </a>
                        </Button>
                        {invoice.can.void && (
                            <VoidInvoiceDialog invoice={invoice} />
                        )}
                    </div>
                </div>

                <Card className="gap-0 py-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6">
                                    Description
                                </TableHead>
                                <TableHead className="text-right">
                                    Qty
                                </TableHead>
                                <TableHead className="text-right">
                                    Unit price
                                </TableHead>
                                <TableHead className="pr-6 text-right">
                                    Amount
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="pl-6 whitespace-normal">
                                        {item.description}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {item.quantity}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {format(item.unit_price)}
                                    </TableCell>
                                    <TableCell className="pr-6 text-right tabular-nums">
                                        {format(
                                            item.amount ??
                                                item.quantity * item.unit_price,
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <CardContent className="border-t py-4">
                        <dl className="ml-auto grid max-w-xs grid-cols-[1fr_auto] gap-x-6 gap-y-1 text-sm">
                            <dt className="text-muted-foreground">Subtotal</dt>
                            <dd className="text-right tabular-nums">
                                {format(invoice.subtotal)}
                            </dd>
                            {invoice.discount > 0 && (
                                <>
                                    <dt className="text-muted-foreground">
                                        Discount
                                    </dt>
                                    <dd className="text-right tabular-nums">
                                        −{format(invoice.discount)}
                                    </dd>
                                </>
                            )}
                            <dt className="font-medium">Total</dt>
                            <dd className="text-right font-medium tabular-nums">
                                {format(invoice.total)}
                            </dd>
                            <dt className="text-muted-foreground">Paid</dt>
                            <dd className="text-right tabular-nums">
                                {format(invoice.paid)}
                            </dd>
                            <dt className="text-base font-semibold">Balance</dt>
                            <dd
                                className="text-right text-base font-semibold tabular-nums"
                                data-test="invoice-balance"
                            >
                                {invoice.status === 'void'
                                    ? '—'
                                    : format(invoice.balance)}
                            </dd>
                        </dl>
                    </CardContent>
                </Card>

                {invoice.notes && (
                    <div className="space-y-1">
                        <h2 className="text-sm font-medium">Notes</h2>
                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                            {invoice.notes}
                        </p>
                    </div>
                )}

                <Card className="gap-4">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Payment history
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {payments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No payments recorded yet.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Received by</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell>
                                                {formatDate(payment.paid_on)}
                                            </TableCell>
                                            <TableCell>
                                                {payment.method_label}
                                            </TableCell>
                                            <TableCell>
                                                {payment.received_by ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {format(payment.amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
