import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import AppointmentDetailsDialog from '@/components/appointment-details-dialog';
import { appointmentStatusStyles } from '@/components/appointment-status-badge';
import AppointmentsHeader from '@/components/appointments-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatOpeningHours, timeToMinutes } from '@/lib/clinic';
import { addDays, formatDay } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { layoutDay } from '@/lib/week-layout';
import { index, week as weekRoute } from '@/routes/appointments';
import type {
    Appointment,
    AppointmentStatus,
    ClinicSchedule,
    Doctor,
    Week,
} from '@/types';

type Props = {
    week: Week;
    appointments: Appointment[];
    filters: { doctor: number | null };
    today: string;
    now: string;
    doctors: Doctor[];
    clinic: ClinicSchedule;
};

const ALL = 'all';

/** The height of one hour in the grid, in pixels. */
const HOUR_HEIGHT = 64;

const legend: { status: AppointmentStatus; label: string }[] = [
    { status: 'scheduled', label: 'Scheduled' },
    { status: 'checked_in', label: 'Checked in' },
    { status: 'completed', label: 'Completed' },
    { status: 'cancelled', label: 'Cancelled' },
    { status: 'no_show', label: 'No-show' },
];

const pad = (value: number): string => String(value).padStart(2, '0');

export default function AppointmentsWeek({
    week,
    appointments,
    filters,
    today,
    now,
    doctors,
    clinic,
}: Props) {
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const selected =
        appointments.find((appointment) => appointment.id === selectedId) ??
        null;

    const firstHour = Math.floor(timeToMinutes(clinic.opens_at) / 60);
    const lastHour = Math.ceil(timeToMinutes(clinic.closes_at) / 60);
    const hours = Array.from(
        { length: lastHour - firstHour },
        (_, offset) => firstHour + offset,
    );
    const gridHeight = hours.length * HOUR_HEIGHT;
    const toPixels = (time: string) =>
        ((timeToMinutes(time) - firstHour * 60) / 60) * HOUR_HEIGHT;

    const nowOffset = toPixels(now);
    const showDoctor = doctors.length > 0 && filters.doctor === null;
    const doctorQuery = filters.doctor ?? undefined;

    const byDay = new Map<string, Appointment[]>();

    for (const appointment of appointments) {
        byDay.set(appointment.date, [
            ...(byDay.get(appointment.date) ?? []),
            appointment,
        ]);
    }

    return (
        <>
            <Head title="Appointments this week" />

            <div className="flex h-full flex-1 flex-col gap-4 px-4 py-6">
                <AppointmentsHeader view="week" doctor={filters.doctor} />

                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-1">
                        <Button variant="outline" size="icon" asChild>
                            <Link
                                href={weekRoute({
                                    query: {
                                        week: week.previous,
                                        doctor: doctorQuery,
                                    },
                                })}
                                aria-label="Previous week"
                            >
                                <ChevronLeft />
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={weekRoute({
                                    query: { doctor: doctorQuery },
                                })}
                            >
                                This week
                            </Link>
                        </Button>
                        <Button variant="outline" size="icon" asChild>
                            <Link
                                href={weekRoute({
                                    query: {
                                        week: week.next,
                                        doctor: doctorQuery,
                                    },
                                })}
                                aria-label="Next week"
                            >
                                <ChevronRight />
                            </Link>
                        </Button>
                    </div>

                    <h2 className="text-base font-medium">
                        {formatDay(week.start)} –{' '}
                        {formatDay(addDays(week.start, 6))}
                    </h2>

                    {doctors.length > 0 && (
                        <Select
                            value={
                                filters.doctor ? String(filters.doctor) : ALL
                            }
                            onValueChange={(value) =>
                                router.get(
                                    weekRoute({
                                        query: {
                                            week: week.start,
                                            doctor:
                                                doctors.find(
                                                    (doctor) =>
                                                        String(doctor.id) ===
                                                        value,
                                                )?.id ?? undefined,
                                        },
                                    }),
                                    {},
                                    { preserveScroll: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger
                                className="ml-auto w-48"
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
                </div>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div
                        className={cn(
                            'grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))]',
                            // Several doctors' appointments share each day
                            // column, so give them room and scroll instead.
                            showDoctor ? 'min-w-[80rem]' : 'min-w-[52rem]',
                        )}
                    >
                        <div className="border-b" />
                        {week.days.map((day) => (
                            <div
                                key={day.date}
                                className={cn(
                                    'border-b border-l px-2 py-2 text-center text-sm',
                                    day.is_today && 'bg-primary/5',
                                )}
                            >
                                <Link
                                    href={index({
                                        query: {
                                            from: day.date,
                                            doctor: doctorQuery,
                                        },
                                    })}
                                    className={cn(
                                        'font-medium hover:underline',
                                        day.is_today && 'text-primary',
                                    )}
                                >
                                    {formatDay(day.date)}
                                </Link>
                                <div className="text-xs text-muted-foreground">
                                    {day.is_working_day
                                        ? `${byDay.get(day.date)?.length ?? 0} booked`
                                        : 'Closed'}
                                </div>
                            </div>
                        ))}

                        <div
                            className="relative"
                            style={{ height: gridHeight }}
                        >
                            {hours.map((hour) => (
                                <div
                                    key={hour}
                                    className="absolute right-2 text-xs text-muted-foreground tabular-nums"
                                    style={{
                                        top:
                                            (hour - firstHour) * HOUR_HEIGHT +
                                            2,
                                    }}
                                >
                                    {pad(hour)}:00
                                </div>
                            ))}
                        </div>

                        {week.days.map((day) => (
                            <div
                                key={day.date}
                                className={cn(
                                    'relative border-l',
                                    !day.is_working_day && 'bg-muted/50',
                                    day.is_today && 'bg-primary/5',
                                )}
                                style={{ height: gridHeight }}
                            >
                                {hours.map((hour) => (
                                    <div
                                        key={hour}
                                        className="absolute inset-x-0 border-t border-dashed border-border/70"
                                        style={{
                                            top:
                                                (hour - firstHour) *
                                                HOUR_HEIGHT,
                                        }}
                                    />
                                ))}

                                {layoutDay(byDay.get(day.date) ?? []).map(
                                    ({ appointment, lane, lanes }) => {
                                        const top = toPixels(
                                            appointment.start_time,
                                        );
                                        const height =
                                            toPixels(appointment.end_time) -
                                            top;
                                        const inactive =
                                            appointment.status ===
                                                'cancelled' ||
                                            appointment.status === 'no_show';

                                        return (
                                            <button
                                                key={appointment.id}
                                                type="button"
                                                onClick={() =>
                                                    setSelectedId(
                                                        appointment.id,
                                                    )
                                                }
                                                aria-label={`${appointment.start_time} to ${appointment.end_time}, ${appointment.patient.full_name}, ${appointment.status_label}`}
                                                className={cn(
                                                    'absolute overflow-hidden rounded-md border px-1.5 py-0.5 text-left text-xs leading-tight shadow-xs transition-shadow hover:z-10 hover:shadow-md focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                                    appointmentStatusStyles[
                                                        appointment.status
                                                    ],
                                                    inactive && 'opacity-60',
                                                )}
                                                style={{
                                                    top: top + 1,
                                                    height: Math.max(
                                                        height - 2,
                                                        16,
                                                    ),
                                                    left: `calc(${(lane / lanes) * 100}% + 2px)`,
                                                    width: `calc(${100 / lanes}% - 4px)`,
                                                }}
                                            >
                                                <div
                                                    className={cn(
                                                        'truncate font-medium',
                                                        inactive &&
                                                            'line-through',
                                                    )}
                                                >
                                                    <span className="tabular-nums">
                                                        {appointment.start_time}
                                                    </span>{' '}
                                                    {
                                                        appointment.patient
                                                            .full_name
                                                    }
                                                </div>
                                                {height >= 40 && (
                                                    <div className="truncate opacity-80">
                                                        {showDoctor
                                                            ? appointment.doctor
                                                                  .name
                                                            : appointment.reason}
                                                    </div>
                                                )}
                                            </button>
                                        );
                                    },
                                )}

                                {day.date === today &&
                                    nowOffset >= 0 &&
                                    nowOffset <= gridHeight && (
                                        <div
                                            className="pointer-events-none absolute inset-x-0 z-20 border-t-2 border-red-500"
                                            style={{ top: nowOffset }}
                                        >
                                            <span className="sr-only">
                                                Now ({now})
                                            </span>
                                        </div>
                                    )}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                    {legend.map((item) => (
                        <span
                            key={item.status}
                            className="flex items-center gap-1.5"
                        >
                            <span
                                className={cn(
                                    'size-3 rounded-sm border',
                                    appointmentStatusStyles[item.status],
                                )}
                            />
                            {item.label}
                        </span>
                    ))}
                    <span className="ml-auto">
                        Clinic hours: {formatOpeningHours(clinic)}
                    </span>
                </div>
            </div>

            <AppointmentDetailsDialog
                appointment={selected}
                onClose={() => setSelectedId(null)}
            />
        </>
    );
}

AppointmentsWeek.layout = {
    breadcrumbs: [
        {
            title: 'Appointments',
            href: index(),
        },
        {
            title: 'Week',
            href: weekRoute(),
        },
    ],
};
