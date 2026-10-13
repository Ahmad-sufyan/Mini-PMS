import type { ReactNode } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    LabelList,
    XAxis,
    YAxis,
} from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { useCurrency } from '@/hooks/use-currency';
import { formatDay } from '@/lib/dates';
import type { AdminDashboard } from '@/types';

/*
 * Each chart shows a single series, so it uses one colour (no legend; the
 * title names it): slot 1 of the validated reference palette, stepped for
 * the light and dark surfaces. Text stays in the text colours.
 */
const SERIES = { light: '#2a78d6', dark: '#3987e5' };

const BAR = {
    radius: [4, 4, 0, 0] as [number, number, number, number],
    maxBarSize: 24,
};

const monthFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});

const shortMonthFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    timeZone: 'UTC',
});

const shortDayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    timeZone: 'UTC',
});

const formatMonth = (month: string): string =>
    monthFormatter.format(new Date(`${month}-01T00:00:00Z`));

const formatShortMonth = (month: string): string =>
    shortMonthFormatter.format(new Date(`${month}-01T00:00:00Z`));

const formatShortDay = (date: string): string =>
    shortDayFormatter.format(new Date(`${date}T00:00:00Z`));

function ChartCard({
    title,
    description,
    children,
    className,
}: {
    title: string;
    description: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={className}>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

/**
 * A visually hidden table with the chart's data, for screen readers.
 */
function DataTable({
    caption,
    headers,
    rows,
}: {
    caption: string;
    headers: [string, string];
    rows: [string, string][];
}) {
    return (
        <table className="sr-only">
            <caption>{caption}</caption>
            <thead>
                <tr>
                    <th scope="col">{headers[0]}</th>
                    <th scope="col">{headers[1]}</th>
                </tr>
            </thead>
            <tbody>
                {rows.map(([label, value]) => (
                    <tr key={label}>
                        <th scope="row">{label}</th>
                        <td>{value}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

export function AppointmentsPerDayChart({
    data,
    className,
}: {
    data: AdminDashboard['appointmentsPerDay'];
    className?: string;
}) {
    const config = {
        count: { label: 'Appointments', theme: SERIES },
    } satisfies ChartConfig;

    return (
        <ChartCard
            title="Appointments per day"
            description="The last 30 days, not counting cancellations"
            className={className}
        >
            <ChartContainer
                config={config}
                className="aspect-auto h-60 w-full"
                aria-hidden
            >
                <BarChart data={data} margin={{ top: 8, right: 8, left: -8 }}>
                    <CartesianGrid vertical={false} />
                    <XAxis
                        dataKey="date"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        minTickGap={24}
                        tickFormatter={formatShortDay}
                    />
                    <YAxis
                        allowDecimals={false}
                        tickLine={false}
                        axisLine={false}
                        width={36}
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--muted)' }}
                        content={
                            <ChartTooltipContent
                                labelFormatter={(_, payload) =>
                                    formatDay(
                                        String(payload[0]?.payload?.date ?? ''),
                                    )
                                }
                            />
                        }
                    />
                    <Bar dataKey="count" fill="var(--color-count)" {...BAR} />
                </BarChart>
            </ChartContainer>
            <DataTable
                caption="Appointments per day, the last 30 days"
                headers={['Day', 'Appointments']}
                rows={data.map((day) => [
                    formatDay(day.date),
                    String(day.count),
                ])}
            />
        </ChartCard>
    );
}

export function RevenuePerMonthChart({
    data,
    className,
}: {
    data: AdminDashboard['revenuePerMonth'];
    className?: string;
}) {
    const { currency, format } = useCurrency();
    const config = {
        total: { label: 'Revenue', theme: SERIES },
    } satisfies ChartConfig;

    const compact = new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency.code,
        notation: 'compact',
        maximumFractionDigits: 1,
    });

    return (
        <ChartCard
            title="Revenue per month"
            description="Payments received over the last 6 months"
            className={className}
        >
            <ChartContainer
                config={config}
                className="aspect-auto h-60 w-full"
                aria-hidden
            >
                <BarChart data={data} margin={{ top: 20, right: 8, left: 0 }}>
                    <CartesianGrid vertical={false} />
                    <XAxis
                        dataKey="month"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        tickFormatter={formatShortMonth}
                    />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        width={56}
                        tickFormatter={(value: number) =>
                            compact.format(value / 10 ** currency.decimals)
                        }
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--muted)' }}
                        content={
                            <ChartTooltipContent
                                labelFormatter={(_, payload) =>
                                    formatMonth(
                                        String(
                                            payload[0]?.payload?.month ?? '',
                                        ),
                                    )
                                }
                                formatter={(value) => (
                                    <span className="font-mono font-medium text-foreground tabular-nums">
                                        {format(Number(value))}
                                    </span>
                                )}
                            />
                        }
                    />
                    <Bar dataKey="total" fill="var(--color-total)" {...BAR}>
                        <LabelList
                            dataKey="total"
                            position="top"
                            offset={6}
                            className="fill-muted-foreground"
                            fontSize={11}
                            formatter={(value: unknown) =>
                                compact.format(
                                    Number(value) / 10 ** currency.decimals,
                                )
                            }
                        />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <DataTable
                caption="Revenue per month, the last 6 months"
                headers={['Month', 'Revenue']}
                rows={data.map((month) => [
                    formatMonth(month.month),
                    format(month.total),
                ])}
            />
        </ChartCard>
    );
}

export function AppointmentsByStatusChart({
    data,
    className,
}: {
    data: AdminDashboard['appointmentsByStatus'];
    className?: string;
}) {
    const config = {
        count: { label: 'Appointments', theme: SERIES },
    } satisfies ChartConfig;

    return (
        <ChartCard
            title="Appointments by status"
            description="The last 30 days"
            className={className}
        >
            <ChartContainer
                config={config}
                className="aspect-auto h-60 w-full"
                aria-hidden
            >
                <BarChart
                    data={data}
                    layout="vertical"
                    margin={{ top: 0, right: 40, left: 8, bottom: 0 }}
                >
                    <XAxis type="number" dataKey="count" hide />
                    <YAxis
                        type="category"
                        dataKey="label"
                        tickLine={false}
                        axisLine={false}
                        width={84}
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--muted)' }}
                        content={<ChartTooltipContent hideIndicator />}
                    />
                    <Bar
                        dataKey="count"
                        fill="var(--color-count)"
                        radius={[0, 4, 4, 0]}
                        maxBarSize={24}
                    >
                        <LabelList
                            dataKey="count"
                            position="right"
                            offset={8}
                            className="fill-foreground"
                            fontSize={12}
                        />
                    </Bar>
                </BarChart>
            </ChartContainer>
            <DataTable
                caption="Appointments by status, the last 30 days"
                headers={['Status', 'Appointments']}
                rows={data.map((status) => [
                    status.label,
                    String(status.count),
                ])}
            />
        </ChartCard>
    );
}
