import { Link } from '@inertiajs/react';
import { FilePlus, Plus, Receipt } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import InvoiceStatusBadge from '@/components/invoice-status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCurrency } from '@/hooks/use-currency';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDate, formatDay } from '@/lib/dates';
import { create, show } from '@/routes/invoices';
import type { Invoice, Patient, UninvoicedVisit } from '@/types';

/**
 * The Billing tab of the patient page. Only billing staff receive this data.
 */
export default function PatientBilling({
    patient,
    invoices,
    uninvoicedVisits,
}: {
    patient: Patient;
    invoices: Invoice[];
    uninvoicedVisits: UninvoicedVisit[];
}) {
    const { can } = usePermissions();
    const { format } = useCurrency();
    const canInvoice = can('billing.manage') && patient.deleted_at === null;

    const outstanding = invoices
        .filter((invoice) => invoice.status !== 'void')
        .reduce((sum, invoice) => sum + invoice.balance, 0);

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div className="text-sm text-muted-foreground">
                        Outstanding
                    </div>
                    <div
                        className="text-2xl font-semibold tabular-nums"
                        data-test="patient-outstanding"
                    >
                        {format(outstanding)}
                    </div>
                </div>

                {canInvoice && (
                    <Button variant="outline" asChild>
                        <Link href={create({ query: { patient: patient.id } })}>
                            <Plus />
                            New invoice
                        </Link>
                    </Button>
                )}
            </div>

            {canInvoice && uninvoicedVisits.length > 0 && (
                <section className="space-y-2">
                    <h3 className="text-sm font-medium">
                        Visits not invoiced yet{' '}
                        <span className="font-normal text-muted-foreground">
                            ({uninvoicedVisits.length})
                        </span>
                    </h3>
                    <ul className="divide-y rounded-xl border">
                        {uninvoicedVisits.map((visit) => (
                            <li
                                key={visit.id}
                                className="flex flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm"
                            >
                                <span>
                                    {formatDay(visit.date)} with {visit.doctor}
                                </span>
                                <Button size="sm" variant="ghost" asChild>
                                    <Link
                                        href={create({
                                            query: { visit: visit.id },
                                        })}
                                        data-test="invoice-visit-button"
                                    >
                                        <FilePlus />
                                        Create invoice
                                    </Link>
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {invoices.length === 0 ? (
                <EmptyState
                    icon={Receipt}
                    title="No invoices yet"
                    description="Invoices and payments for this patient will appear here."
                />
            ) : (
                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">Invoice</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Issued
                                </TableHead>
                                <TableHead className="hidden text-right md:table-cell">
                                    Total
                                </TableHead>
                                <TableHead className="text-right">
                                    Balance
                                </TableHead>
                                <TableHead className="pr-4">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {invoices.map((invoice) => (
                                <TableRow key={invoice.id}>
                                    <TableCell className="pl-4">
                                        <Link
                                            href={show(invoice.id)}
                                            className="font-mono text-xs font-medium hover:underline"
                                        >
                                            {invoice.invoice_number}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {formatDate(invoice.issued_on)}
                                    </TableCell>
                                    <TableCell className="hidden text-right tabular-nums md:table-cell">
                                        {format(invoice.total)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium tabular-nums">
                                        {invoice.status === 'void'
                                            ? '—'
                                            : format(invoice.balance)}
                                    </TableCell>
                                    <TableCell className="pr-4">
                                        <InvoiceStatusBadge
                                            status={invoice.status}
                                            label={invoice.status_label}
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </div>
    );
}
