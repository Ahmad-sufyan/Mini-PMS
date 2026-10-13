import { Head, Link, router } from '@inertiajs/react';
import { Plus, Receipt, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import InvoiceStatusBadge from '@/components/invoice-status-badge';
import InvoiceTotals from '@/components/invoice-totals';
import TablePagination from '@/components/table-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { formatDate } from '@/lib/dates';
import { create, index, show } from '@/routes/invoices';
import type {
    Invoice,
    InvoiceFilters,
    InvoiceStatus,
    InvoiceTotals as Totals,
    Paginated,
    SelectOption,
} from '@/types';

type Props = {
    invoices: Paginated<Invoice>;
    totals: Totals;
    filters: InvoiceFilters;
    statuses: SelectOption<InvoiceStatus>[];
};

const ALL = 'all';

function listUrl(filters: InvoiceFilters) {
    return index({
        query: {
            search: filters.search || undefined,
            status: filters.status ?? undefined,
            from: filters.from ?? undefined,
            to: filters.to ?? undefined,
        },
    });
}

function visitList(filters: InvoiceFilters): void {
    router.get(
        listUrl(filters),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

export default function InvoicesIndex({
    invoices,
    totals,
    filters,
    statuses,
}: Props) {
    const { can } = usePermissions();
    const { format } = useCurrency();
    const [search, setSearch] = useState(filters.search);

    const current = { ...filters, search: search.trim() };
    const isFiltered =
        current.search !== '' ||
        filters.status !== null ||
        filters.from !== null ||
        filters.to !== null;

    // Search once the user pauses typing.
    useEffect(() => {
        if (search.trim() === filters.search) {
            return;
        }

        const timeout = setTimeout(
            () => visitList({ ...filters, search: search.trim() }),
            300,
        );

        return () => clearTimeout(timeout);
    }, [search, filters]);

    return (
        <>
            <Head title="Billing" />

            <div className="flex h-full flex-1 flex-col gap-4 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Billing"
                        description="Invoices, payments and what's still owed"
                    />

                    {can('billing.manage') && (
                        <Button asChild>
                            <Link
                                href={create()}
                                data-test="new-invoice-button"
                            >
                                <Plus />
                                New invoice
                            </Link>
                        </Button>
                    )}
                </div>

                <InvoiceTotals totals={totals} />

                <div className="flex flex-wrap items-end gap-3">
                    <div className="relative min-w-56 flex-1 sm:max-w-sm">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search patient name or MRN"
                            aria-label="Search by patient"
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={filters.status ?? ALL}
                        onValueChange={(value) =>
                            visitList({
                                ...current,
                                status:
                                    statuses.find(
                                        (status) => status.value === value,
                                    )?.value ?? null,
                            })
                        }
                    >
                        <SelectTrigger
                            className="w-40"
                            aria-label="Filter by status"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All statuses</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <div className="grid gap-1.5">
                        <Label htmlFor="from">Issued from</Label>
                        <Input
                            key={`from-${filters.from}`}
                            id="from"
                            type="date"
                            defaultValue={filters.from ?? ''}
                            className="w-40"
                            onChange={(event) =>
                                visitList({
                                    ...current,
                                    from: event.target.value || null,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="to">to</Label>
                        <Input
                            key={`to-${filters.to}`}
                            id="to"
                            type="date"
                            defaultValue={filters.to ?? ''}
                            min={filters.from ?? undefined}
                            className="w-40"
                            onChange={(event) =>
                                visitList({
                                    ...current,
                                    to: event.target.value || null,
                                })
                            }
                        />
                    </div>

                    {isFiltered && (
                        <Button variant="ghost" asChild>
                            <Link href={index()}>Clear filters</Link>
                        </Button>
                    )}
                </div>

                {filters.status === 'void' && (
                    <p className="text-sm text-muted-foreground">
                        Void invoices don't count towards the totals.
                    </p>
                )}

                {invoices.data.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title={
                            isFiltered
                                ? 'No invoices match your filters'
                                : 'No invoices yet'
                        }
                        description={
                            isFiltered
                                ? 'Try a different patient, status or date range.'
                                : 'Invoices you create will appear here.'
                        }
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">
                                        Invoice
                                    </TableHead>
                                    <TableHead>Patient</TableHead>
                                    <TableHead className="hidden sm:table-cell">
                                        Issued
                                    </TableHead>
                                    <TableHead className="hidden text-right md:table-cell">
                                        Total
                                    </TableHead>
                                    <TableHead className="hidden text-right lg:table-cell">
                                        Paid
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Balance
                                    </TableHead>
                                    <TableHead className="hidden pr-4 sm:table-cell">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="pl-4">
                                            <Link
                                                href={show(invoice.id)}
                                                className="font-mono text-xs font-medium hover:underline"
                                            >
                                                {invoice.invoice_number}
                                            </Link>
                                            <div className="mt-1 sm:hidden">
                                                <InvoiceStatusBadge
                                                    status={invoice.status}
                                                    label={invoice.status_label}
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            <div className="font-medium">
                                                {invoice.patient.full_name}
                                            </div>
                                            <div className="font-mono text-xs text-muted-foreground">
                                                {invoice.patient.mrn}
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {formatDate(invoice.issued_on)}
                                        </TableCell>
                                        <TableCell className="hidden text-right tabular-nums md:table-cell">
                                            {format(invoice.total)}
                                        </TableCell>
                                        <TableCell className="hidden text-right tabular-nums lg:table-cell">
                                            {format(invoice.paid)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium tabular-nums">
                                            {invoice.status === 'void'
                                                ? '—'
                                                : format(invoice.balance)}
                                        </TableCell>
                                        <TableCell className="hidden pr-4 sm:table-cell">
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

                <TablePagination paginator={invoices} />
            </div>
        </>
    );
}

InvoicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Billing',
            href: index(),
        },
    ],
};
