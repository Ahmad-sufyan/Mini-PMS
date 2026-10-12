import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useMainNavItems } from '@/hooks/use-main-nav-items';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import type { Clinic } from '@/types';

const formatDay = (day: string): string =>
    day.charAt(0).toUpperCase() + day.slice(1);

export default function Dashboard({ clinic }: { clinic: Clinic }) {
    const { auth } = usePage().props;
    const { role } = usePermissions();
    const modules = useMainNavItems().filter(
        (item) => item.permission !== undefined,
    );

    const details = [
        { label: 'Phone', value: clinic.phone },
        {
            label: 'Opening hours',
            value: `${clinic.opening_hours.opens_at} – ${clinic.opening_hours.closes_at}`,
        },
        {
            label: 'Working days',
            value: clinic.working_days.map(formatDay).join(', '),
        },
        { label: 'Timezone', value: clinic.timezone },
        { label: 'Currency', value: clinic.currency },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="space-y-2">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Welcome back, {auth.user.name}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">{role.label}</Badge>
                        {auth.user.specialization && (
                            <Badge variant="secondary">
                                {auth.user.specialization}
                            </Badge>
                        )}
                    </div>
                </div>

                <div className="grid auto-rows-min gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {modules.map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            prefetch
                            className="group flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                        >
                            {item.icon && (
                                <item.icon className="size-5 text-muted-foreground group-hover:text-foreground" />
                            )}
                            <span className="font-medium">{item.title}</span>
                            <ArrowRight className="ml-auto size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    ))}
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{clinic.name}</CardTitle>
                        <CardDescription>{clinic.address}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 text-sm sm:grid-cols-2">
                            {details.map((detail) => (
                                <div key={detail.label}>
                                    <dt className="text-muted-foreground">
                                        {detail.label}
                                    </dt>
                                    <dd className="font-medium">
                                        {detail.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
