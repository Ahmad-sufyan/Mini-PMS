import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/hooks/use-currency';
import { formatDate, formatLongDay } from '@/lib/dates';
import { show } from '@/routes/invoices';
import type { Invoice } from '@/types';

type Props = {
    invoice: Invoice;
    clinic: {
        name: string;
        address: string;
        phone: string;
    };
};

/**
 * A printable invoice (A4). It renders without the app layout, and the
 * toolbar is hidden when printing.
 */
export default function PrintInvoice({ invoice, clinic }: Props) {
    const { format } = useCurrency();
    const items = invoice.items ?? [];
    const payments = invoice.payments ?? [];

    return (
        <>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="min-h-screen bg-neutral-100 py-8 dark:bg-neutral-900 print:min-h-0 print:bg-white print:py-0">
                <div className="mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-4 px-4 print:hidden">
                    <Button variant="ghost" asChild>
                        <Link href={show(invoice.id)}>
                            <ArrowLeft />
                            Back to {invoice.invoice_number}
                        </Link>
                    </Button>
                    <Button
                        onClick={() => window.print()}
                        data-test="print-invoice-button"
                    >
                        <Printer />
                        Print
                    </Button>
                </div>

                <article className="mx-auto max-w-[210mm] bg-white p-[16mm] text-sm text-neutral-900 shadow-md print:max-w-none print:p-0 print:shadow-none">
                    <header className="flex items-start justify-between gap-6 border-b-2 border-neutral-900 pb-4">
                        <div>
                            <h1 className="text-xl font-bold">{clinic.name}</h1>
                            <p>{clinic.address}</p>
                            <p>Tel: {clinic.phone}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-2xl font-bold tracking-wide">
                                {invoice.status === 'void'
                                    ? 'VOID INVOICE'
                                    : 'INVOICE'}
                            </p>
                            <p className="font-mono">
                                {invoice.invoice_number}
                            </p>
                        </div>
                    </header>

                    <section className="mt-6 flex justify-between gap-8">
                        <div>
                            <p className="text-neutral-500">Bill to</p>
                            <p className="font-semibold">
                                {invoice.patient.full_name}
                            </p>
                            <p>MRN: {invoice.patient.mrn}</p>
                        </div>
                        <div className="text-right">
                            <p>
                                <span className="text-neutral-500">Date: </span>
                                {formatLongDay(invoice.issued_on)}
                            </p>
                            <p>
                                <span className="text-neutral-500">
                                    Status:{' '}
                                </span>
                                {invoice.status_label}
                            </p>
                        </div>
                    </section>

                    <table className="mt-8 w-full border-collapse text-left">
                        <thead>
                            <tr className="border-b-2 border-neutral-900">
                                <th className="py-2 pr-2 font-semibold">
                                    Description
                                </th>
                                <th className="py-2 pr-2 text-right font-semibold">
                                    Qty
                                </th>
                                <th className="py-2 pr-2 text-right font-semibold">
                                    Unit price
                                </th>
                                <th className="py-2 text-right font-semibold">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item) => (
                                <tr
                                    key={item.id}
                                    className="break-inside-avoid border-b border-neutral-300"
                                >
                                    <td className="py-2 pr-2">
                                        {item.description}
                                    </td>
                                    <td className="py-2 pr-2 text-right tabular-nums">
                                        {item.quantity}
                                    </td>
                                    <td className="py-2 pr-2 text-right tabular-nums">
                                        {format(item.unit_price)}
                                    </td>
                                    <td className="py-2 text-right tabular-nums">
                                        {format(
                                            item.amount ??
                                                item.quantity * item.unit_price,
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <dl className="mt-4 ml-auto grid w-64 break-inside-avoid grid-cols-[1fr_auto] gap-x-6 gap-y-1">
                        <dt className="text-neutral-500">Subtotal</dt>
                        <dd className="text-right tabular-nums">
                            {format(invoice.subtotal)}
                        </dd>
                        {invoice.discount > 0 && (
                            <>
                                <dt className="text-neutral-500">Discount</dt>
                                <dd className="text-right tabular-nums">
                                    −{format(invoice.discount)}
                                </dd>
                            </>
                        )}
                        <dt className="font-semibold">Total</dt>
                        <dd className="text-right font-semibold tabular-nums">
                            {format(invoice.total)}
                        </dd>
                        <dt className="text-neutral-500">Paid</dt>
                        <dd className="text-right tabular-nums">
                            {format(invoice.paid)}
                        </dd>
                        <dt className="border-t border-neutral-900 pt-1 text-base font-bold">
                            Balance due
                        </dt>
                        <dd className="border-t border-neutral-900 pt-1 text-right text-base font-bold tabular-nums">
                            {invoice.status === 'void'
                                ? '—'
                                : format(invoice.balance)}
                        </dd>
                    </dl>

                    {payments.length > 0 && (
                        <section className="mt-8 break-inside-avoid">
                            <h2 className="font-semibold">Payments received</h2>
                            <ul className="mt-1 space-y-0.5">
                                {payments.map((payment) => (
                                    <li
                                        key={payment.id}
                                        className="flex justify-between"
                                    >
                                        <span>
                                            {formatDate(payment.paid_on)} ·{' '}
                                            {payment.method_label}
                                        </span>
                                        <span className="tabular-nums">
                                            {format(payment.amount)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {invoice.notes && (
                        <p className="mt-8 whitespace-pre-line">
                            {invoice.notes}
                        </p>
                    )}

                    <footer className="mt-16 border-t border-neutral-300 pt-3 text-center text-neutral-500">
                        Thank you. Please quote {invoice.invoice_number} with
                        any payment.
                    </footer>
                </article>
            </div>
        </>
    );
}
