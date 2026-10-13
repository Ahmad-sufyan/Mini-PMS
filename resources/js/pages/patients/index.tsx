import { Form, Head, Link, router } from '@inertiajs/react';
import { Plus, RotateCcw, Search, TriangleAlert, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import SortableTableHead from '@/components/sortable-table-head';
import TablePagination from '@/components/table-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
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
import { usePermissions } from '@/hooks/use-permissions';
import { formatTimestamp } from '@/lib/dates';
import { create, index, show } from '@/routes/patients';
import type {
    Gender,
    Paginated,
    PatientFilters,
    PatientListItem,
    PatientSort,
    SelectOption,
} from '@/types';

type Props = {
    patients: Paginated<PatientListItem>;
    filters: PatientFilters;
    genders: SelectOption<Gender>[];
};

const ALL_GENDERS = 'all';

/**
 * Build the list URL for the given filters, leaving defaults out of the URL.
 * Changing a filter drops the page number, so the list starts at page one.
 */
function listUrl(filters: PatientFilters) {
    return index({
        query: {
            search: filters.search || undefined,
            gender: filters.gender ?? undefined,
            sort: filters.sort === 'name' ? undefined : filters.sort,
            direction:
                filters.direction === 'asc' ? undefined : filters.direction,
            status: filters.status === 'active' ? undefined : filters.status,
        },
    });
}

function emptyMessage(
    isFiltered: boolean,
    showingDeleted: boolean,
): { title: string; description: string } {
    if (isFiltered) {
        return {
            title: 'No patients match your filters',
            description: 'Try a different name, MRN or phone number.',
        };
    }

    if (showingDeleted) {
        return {
            title: 'No deleted records',
            description:
                'Deleted patient records appear here so they can be restored.',
        };
    }

    return {
        title: 'No patients yet',
        description: 'Registered patients will appear here.',
    };
}

function visitList(filters: PatientFilters): void {
    router.get(
        listUrl(filters),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

export default function PatientsIndex({ patients, filters, genders }: Props) {
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters.search);

    const current = { ...filters, search: search.trim() };
    const isFiltered = current.search !== '' || filters.gender !== null;
    const showingDeleted = filters.status === 'deleted';

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

    const sortLink = (
        column: PatientSort,
        label: string,
        className?: string,
    ) => (
        <SortableTableHead
            label={label}
            className={className}
            href={listUrl({
                ...current,
                sort: column,
                direction:
                    filters.sort === column && filters.direction === 'asc'
                        ? 'desc'
                        : 'asc',
            })}
            direction={filters.sort === column ? filters.direction : null}
        />
    );

    const genderLabel = (gender: Gender): string =>
        genders.find((option) => option.value === gender)?.label ?? gender;

    return (
        <>
            <Head title="Patients" />

            <div className="flex h-full flex-1 flex-col gap-4 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Patients"
                        description="Search the clinic's patient records"
                    />

                    {can('patients.manage') && (
                        <Button asChild>
                            <Link
                                href={create()}
                                data-test="add-patient-button"
                            >
                                <Plus />
                                Register patient
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative sm:max-w-sm sm:flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name, MRN or phone"
                            aria-label="Search patients"
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={filters.gender ?? ALL_GENDERS}
                        onValueChange={(value) =>
                            visitList({
                                ...current,
                                gender:
                                    genders.find(
                                        (option) => option.value === value,
                                    )?.value ?? null,
                            })
                        }
                    >
                        <SelectTrigger
                            className="sm:w-40"
                            aria-label="Filter by gender"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_GENDERS}>
                                All genders
                            </SelectItem>
                            {genders.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {can('patients.delete') && (
                        <Select
                            value={filters.status}
                            onValueChange={(value) =>
                                visitList({
                                    ...current,
                                    status:
                                        value === 'deleted'
                                            ? 'deleted'
                                            : 'active',
                                })
                            }
                        >
                            <SelectTrigger
                                className="sm:w-44"
                                aria-label="Filter by status"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">
                                    Active records
                                </SelectItem>
                                <SelectItem value="deleted">
                                    Deleted records
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    )}
                </div>

                {patients.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        {...emptyMessage(isFiltered, showingDeleted)}
                    >
                        {isFiltered && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={listUrl({
                                        ...filters,
                                        search: '',
                                        gender: null,
                                    })}
                                >
                                    Clear filters
                                </Link>
                            </Button>
                        )}
                    </EmptyState>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {sortLink(
                                        'mrn',
                                        'MRN',
                                        'hidden pl-4 sm:table-cell',
                                    )}
                                    {sortLink('name', 'Name', 'pl-4 sm:pl-2')}
                                    {sortLink('age', 'Age')}
                                    <TableHead className="hidden md:table-cell">
                                        Gender
                                    </TableHead>
                                    <TableHead className="hidden sm:table-cell">
                                        Phone
                                    </TableHead>
                                    {showingDeleted ? (
                                        <TableHead className="hidden lg:table-cell">
                                            Deleted
                                        </TableHead>
                                    ) : (
                                        sortLink(
                                            'registered',
                                            'Registered',
                                            'hidden lg:table-cell',
                                        )
                                    )}
                                    <TableHead className="pr-4">
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {patients.data.map((patient) => (
                                    <TableRow key={patient.id}>
                                        <TableCell className="hidden pl-4 font-mono text-xs sm:table-cell">
                                            {patient.mrn}
                                        </TableCell>
                                        <TableCell className="pl-4 whitespace-normal sm:pl-2">
                                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <Link
                                                    href={show(patient.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {patient.full_name}
                                                </Link>
                                                {patient.allergies && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-amber-300 text-amber-700 dark:border-amber-500/40 dark:text-amber-400"
                                                        title={`Allergies: ${patient.allergies}`}
                                                    >
                                                        <TriangleAlert />
                                                        Allergies
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="font-mono text-xs text-muted-foreground sm:hidden">
                                                {patient.mrn}
                                            </div>
                                        </TableCell>
                                        <TableCell>{patient.age}</TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {genderLabel(patient.gender)}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {patient.phone}
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {formatTimestamp(
                                                patient.deleted_at ??
                                                    patient.created_at,
                                            )}
                                        </TableCell>
                                        <TableCell className="pr-4">
                                            <div className="flex justify-end gap-2">
                                                {showingDeleted ? (
                                                    <Form
                                                        {...PatientController.restore.form(
                                                            patient.id,
                                                        )}
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                {processing ? (
                                                                    <Spinner />
                                                                ) : (
                                                                    <RotateCcw />
                                                                )}
                                                                Restore
                                                            </Button>
                                                        )}
                                                    </Form>
                                                ) : (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={show(
                                                                patient.id,
                                                            )}
                                                        >
                                                            View
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <TablePagination paginator={patients} />
            </div>
        </>
    );
}

PatientsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Patients',
            href: index(),
        },
    ],
};
