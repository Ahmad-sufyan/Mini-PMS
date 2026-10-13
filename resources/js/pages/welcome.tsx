import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    ChartColumn,
    ClipboardList,
    LayoutDashboard,
    LogIn,
    Pill,
    Receipt,
    RotateCcw,
    ShieldCheck,
    Stethoscope,
    UserPlus,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login } from '@/routes';

type Feature = {
    icon: LucideIcon;
    text: string;
};

const roles: { title: string; icon: LucideIcon; features: Feature[] }[] = [
    {
        title: 'Reception',
        icon: ClipboardList,
        features: [
            { icon: UserPlus, text: 'Register patients with a unique MRN' },
            { icon: CalendarCheck, text: 'Book, check in and reschedule' },
            { icon: Receipt, text: 'Invoices, payments and balances' },
        ],
    },
    {
        title: 'Doctors',
        icon: Stethoscope,
        features: [
            {
                icon: LayoutDashboard,
                text: "Today's schedule, next patient first",
            },
            { icon: ClipboardList, text: 'Consultation notes and vitals' },
            { icon: Pill, text: 'Printable prescriptions' },
        ],
    },
    {
        title: 'Administrators',
        icon: ShieldCheck,
        features: [
            { icon: ChartColumn, text: 'Clinic dashboard with charts' },
            { icon: Users, text: 'Staff accounts and roles' },
            { icon: RotateCcw, text: 'Void invoices and restore records' },
        ],
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-4">
                    <div className="flex items-center gap-1">
                        <AppLogo />
                    </div>

                    <nav>
                        {auth.user ? (
                            <Button variant="outline" asChild>
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <Button variant="outline" asChild>
                                <Link href={login()}>Log in</Link>
                            </Button>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-12 px-4 py-12">
                    <section className="max-w-2xl space-y-4">
                        <p className="text-sm font-medium text-muted-foreground">
                            Patient management for small clinics
                        </p>
                        <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                            The front desk and the consulting room, in one
                            place.
                        </h1>
                        <p className="text-lg text-pretty text-muted-foreground">
                            Patient records, appointments, consultations,
                            prescriptions and billing, with a dashboard for
                            every role in the clinic.
                        </p>
                        <div className="pt-2">
                            <Button size="lg" asChild>
                                {auth.user ? (
                                    <Link href={dashboard()}>
                                        <LayoutDashboard />
                                        Go to your dashboard
                                    </Link>
                                ) : (
                                    <Link href={login()}>
                                        <LogIn />
                                        Log in to the clinic
                                    </Link>
                                )}
                            </Button>
                        </div>
                    </section>

                    <section
                        aria-label="Features by role"
                        className="grid gap-4 md:grid-cols-3"
                    >
                        {roles.map((role) => (
                            <Card key={role.title} className="gap-4">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <role.icon className="size-5 text-muted-foreground" />
                                        {role.title}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3 text-sm">
                                        {role.features.map((feature) => (
                                            <li
                                                key={feature.text}
                                                className="flex items-start gap-3"
                                            >
                                                <feature.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                                {feature.text}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                </main>

                <footer className="mx-auto w-full max-w-5xl px-4 py-6 text-sm text-muted-foreground">
                    A demo project. Every patient, appointment and invoice in it
                    is made up.
                </footer>
            </div>
        </>
    );
}
