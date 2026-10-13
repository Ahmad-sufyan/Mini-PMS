import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight, Pencil } from 'lucide-react';
import AppointmentStatusActions from '@/components/appointment-status-actions';
import AppointmentStatusBadge from '@/components/appointment-status-badge';
import AppointmentsHeader from '@/components/appointments-header';
import EmptyState from '@/components/empty-state';
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
import { addDays, formatDay, formatLongDay } from '@/lib/dates';
import { edit, index } from '@/routes/appointments';
import { show } from '@/routes/patients';
import type {
    Appointment,
    AppointmentFilters,
    AppointmentStatus,
    Doctor,
    Paginated,
    SelectOption,
} from '@/types';

type Props = {
    appointments: Paginated<Appointment>;
    filters: AppointmentFilters;
    today: string;
    doctors: Doctor[];
    statuses: SelectOption<AppointmentStatus>[];
};

const ALL = 'all';

/**
 * Build the list URL for the given filters, leaving defaults out of the URL.
 */
function listUrl(filters: AppointmentFilters, today: string) {
    return index({
        query: {
            from: filters.from === today ? undefined : filters.from,
            to: filters.to === filters.from ? undefined : filters.to,
            doctor: filters.doctor ?? undefined,
            status: filters.status ?? undefined,
        },
    });
}

/**
 * Count the days in a date range, including both ends.
 */
function daysIn(from: string, to: string): number {
    return Math.round((Date.parse(to) - Date.parse(from)) / 86_400_000) + 1;
}

export default function AppointmentsIndex({
    appointments,
    filters,
    today,
    doctors,
    statuses,
}: Props) {
    const singleDay = filters.from === filters.to;
    const showDoctor = doctors.length > 0;
    const span = daysIn(filters.from, filters.to);

    const visit = (changes: Partial<AppointmentFilters>) => {
        const next = { ...filters, ...changes };

        if (next.to < next.from) {
            next.to = next.from;
        }

        router.get(
            listUrl(next, today),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const shift = (direction: 1 | -1) =>
        visit({
            from: addDays(filters.from, span * direction),
            to: addDays(filters.to, span * direction),
        });

    return (
        <>
            <Head title="Appointments" />

            <div className="flex h-full flex-1 flex-col gap-4 px-4 py-6">
                <AppointmentsHeader view="list" doctor={filters.doctor} />

                <div className="flex flex-wrap items-end gap-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="from">From</Label>
                        <Input
                            key={`from-${filters.from}`}
                            id="from"
                            type="date"
                            defaultValue={filters.from}
                            className="w-40"
                            onChange={(event) =>
                                event.target.value &&
                                visit({ from: event.target.value })
                            }
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="to">To</Label>
                        <Input
                            key={`to-${filters.to}`}
                            id="to"
                            type="date"
                            defaultValue={filters.to}
                            min={filters.from}
                            className="w-40"
                            onChange={(event) =>
                                event.target.value &&
                                visit({ to: event.target.value })
                            }
                        />
                    </div>

                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label={
                                singleDay ? 'Previous day' : 'Previous period'
                            }
                            onClick={() => shift(-1)}
                        >
                            <ChevronLeft />
                        </Button>
                        <Button
                            variant="outline"
                            disabled={singleDay && filters.from === today}
                            onClick={() => visit({ from: today, to: today })}
                        >
                            Today
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label={singleDay ? 'Next day' : 'Next period'}
                            onClick={() => shift(1)}
                        >
                            <ChevronRight />
                        </Button>
                    </div>

                    {showDoctor && (
                        <Select
                            value={
                                filters.doctor ? String(filters.doctor) : ALL
                            }
                            onValueChange={(value) =>
                                visit({
                                    doctor:
                                        doctors.find(
                                            (doctor) =>
                                                String(doctor.id) === value,
                                        )?.id ?? null,
                                })
                            }
                        >
                            <SelectTrigger
                                className="w-48"
                                aria-label="Filter by doctor"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>All doctors</SelectItem>
                                {doctors.map((doctor) => (
                                    <SelectItem
                                        key={doctor.id}
                                        value={String(doctor.id)}
                                    >
                                        {doctor.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}

                    <Select
                        value={filters.status ?? ALL}
                        onValueChange={(value) =>
                            visit({
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
                </div>

                <h2 className="text-base font-medium">
                    {singleDay
                        ? formatLongDay(filters.from)
                        : `${formatDay(filters.from)} – ${formatDay(filters.to)}`}
                    {filters.from === today && singleDay && (
                        <span className="font-normal text-muted-foreground">
                            {' '}
                            · Today
                        </span>
                    )}
                </h2>

                {appointments.data.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="No appointments"
                        description={
                            singleDay
                                ? 'Nothing is booked for this day with these filters.'
                                : 'Nothing is booked in this period with these filters.'
                        }
                    />
                ) : (
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">Time</TableHead>
                                    <TableHead>Patient</TableHead>
                                    {showDoctor && (
                                        <TableHead className="hidden md:table-cell">
                                            Doctor
                                        </TableHead>
                                    )}
                                    <TableHead className="hidden lg:table-cell">
                                        Reason
                                    </TableHead>
                                    <TableHead className="hidden sm:table-cell">
                                        Status
                                    </TableHead>
                                    <TableHead className="pr-4">
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {appointments.data.map((appointment) => (
                                    <TableRow key={appointment.id}>
                                        <TableCell className="pl-4">
                                            <div className="font-medium tabular-nums">
                                                {appointment.start_time}–
                                                {appointment.end_time}
                                            </div>
                                            {!singleDay && (
                                                <div className="text-xs text-muted-foreground">
                                                    {formatDay(
                                                        appointment.date,
                                                    )}
                                                </div>
                                            )}
                                            <div className="mt-1 sm:hidden">
                                                <AppointmentStatusBadge
                                                    status={appointment.status}
                                                    label={
                                                        appointment.status_label
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            <Link
                                                href={show(
                                                    appointment.patient.id,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {appointment.patient.full_name}
                                            </Link>
                                            <div className="font-mono text-xs text-muted-foreground">
                                                {appointment.patient.mrn}
                                            </div>
                                        </TableCell>
                                        {showDoctor && (
                                            <TableCell className="hidden md:table-cell">
                                                <div>
                                                    {appointment.doctor.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {
                                                        appointment.doctor
                                                            .specialization
                                                    }
                                                </div>
                                            </TableCell>
                                        )}
                                        <TableCell className="hidden max-w-56 truncate lg:table-cell">
                                            {appointment.reason}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            <AppointmentStatusBadge
                                                status={appointment.status}
                                                label={appointment.status_label}
                                            />
                                        </TableCell>
                                        <TableCell className="pr-4 whitespace-normal">
                                            <div className="flex flex-wrap items-center justify-end gap-2">
                                                <AppointmentStatusActions
                                                    appointment={appointment}
                                                />
                                                {appointment.can_edit && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(
                                                                appointment.id,
                                                            )}
                                                            aria-label={`Edit ${appointment.patient.full_name}'s appointment`}
                                                        >
                                                            <Pencil />
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

                <TablePagination paginator={appointments} />
            </div>
        </>
    );
}

AppointmentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Appointments',
            href: index(),
        },
    ],
};
