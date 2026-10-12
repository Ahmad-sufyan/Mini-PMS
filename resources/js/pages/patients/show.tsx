import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    CalendarDays,
    Pencil,
    Receipt,
    RotateCcw,
    Stethoscope,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import type { ReactNode } from 'react';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import DeletePatientDialog from '@/components/delete-patient-dialog';
import EmptyState from '@/components/empty-state';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useInitials } from '@/hooks/use-initials';
import { usePermissions } from '@/hooks/use-permissions';
import { formatAge, formatDate, formatTimestamp } from '@/lib/dates';
import { edit, index, show } from '@/routes/patients';
import type { Patient } from '@/types';

const capitalize = (value: string): string =>
    value.charAt(0).toUpperCase() + value.slice(1);

function DetailsCard({
    title,
    details,
}: {
    title: string;
    details: { label: string; value: ReactNode }[];
}) {
    return (
        <Card className="gap-4">
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <dl className="grid gap-3 text-sm">
                    {details.map((detail) => (
                        <div key={detail.label}>
                            <dt className="text-muted-foreground">
                                {detail.label}
                            </dt>
                            <dd className="font-medium break-words whitespace-pre-line">
                                {detail.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </CardContent>
        </Card>
    );
}

export default function ShowPatient({ patient }: { patient: Patient }) {
    const { can } = usePermissions();
    const getInitials = useInitials();
    const isDeleted = patient.deleted_at !== null;

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Patients',
                href: index(),
            },
            {
                title: patient.full_name,
                href: show(patient.id),
            },
        ],
    });

    const notRecorded = (
        <span className="font-normal text-muted-foreground">Not recorded</span>
    );

    return (
        <>
            <Head title={patient.full_name} />

            <div className="flex flex-col gap-6 px-4 py-6">
                {patient.deleted_at !== null && (
                    <Alert variant="destructive">
                        <Trash2 />
                        <AlertTitle>
                            This record was deleted on{' '}
                            {formatTimestamp(patient.deleted_at)}
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                It's hidden from the patient list. Restore it to
                                edit the record again.
                            </p>
                            {can('patients.delete') && (
                                <Form
                                    {...PatientController.restore.form(
                                        patient.id,
                                    )}
                                    className="mt-2"
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                            data-test="restore-patient-button"
                                        >
                                            <RotateCcw />
                                            Restore record
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardContent className="flex flex-col gap-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex items-start gap-4">
                                <Avatar className="size-14">
                                    <AvatarFallback className="text-lg">
                                        {getInitials(patient.full_name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="space-y-2">
                                    <h1 className="text-xl font-semibold tracking-tight">
                                        {patient.full_name}
                                    </h1>
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <Badge
                                            variant="secondary"
                                            className="font-mono"
                                        >
                                            {patient.mrn}
                                        </Badge>
                                        <Badge variant="outline">
                                            {formatAge(patient.age)}
                                        </Badge>
                                        <Badge variant="outline">
                                            {capitalize(patient.gender)}
                                        </Badge>
                                        <Badge variant="outline">
                                            Blood group{' '}
                                            {patient.blood_group ?? 'unknown'}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            {!isDeleted && (
                                <div className="flex gap-2">
                                    {can('patients.manage') && (
                                        <Button asChild>
                                            <Link
                                                href={edit(patient.id)}
                                                data-test="edit-patient-button"
                                            >
                                                <Pencil />
                                                Edit
                                            </Link>
                                        </Button>
                                    )}
                                    {can('patients.delete') && (
                                        <DeletePatientDialog
                                            patient={patient}
                                        />
                                    )}
                                </div>
                            )}
                        </div>

                        {patient.allergies ? (
                            <Alert className="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                                <TriangleAlert />
                                <AlertTitle>Allergies</AlertTitle>
                                <AlertDescription className="text-amber-900 dark:text-amber-200">
                                    {patient.allergies}
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No known allergies recorded.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Tabs defaultValue="overview" className="gap-4">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="appointments">
                            Appointments
                        </TabsTrigger>
                        <TabsTrigger value="visits">Visits</TabsTrigger>
                        <TabsTrigger value="billing">Billing</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <DetailsCard
                                title="Personal details"
                                details={[
                                    {
                                        label: 'Date of birth',
                                        value: formatDate(
                                            patient.date_of_birth,
                                        ),
                                    },
                                    {
                                        label: 'Gender',
                                        value: capitalize(patient.gender),
                                    },
                                    {
                                        label: 'Blood group',
                                        value:
                                            patient.blood_group ?? notRecorded,
                                    },
                                    {
                                        label: 'Registered',
                                        value: formatTimestamp(
                                            patient.created_at,
                                        ),
                                    },
                                ]}
                            />
                            <DetailsCard
                                title="Contact"
                                details={[
                                    { label: 'Phone', value: patient.phone },
                                    {
                                        label: 'Email',
                                        value: patient.email ?? notRecorded,
                                    },
                                    {
                                        label: 'Address',
                                        value: patient.address,
                                    },
                                ]}
                            />
                            <DetailsCard
                                title="Emergency contact"
                                details={[
                                    {
                                        label: 'Name',
                                        value: patient.emergency_contact_name,
                                    },
                                    {
                                        label: 'Phone',
                                        value: patient.emergency_contact_phone,
                                    },
                                ]}
                            />
                            <div className="md:col-span-2 xl:col-span-3">
                                <DetailsCard
                                    title="Notes"
                                    details={[
                                        {
                                            label: 'Clinical and admin notes',
                                            value: patient.notes ?? notRecorded,
                                        },
                                    ]}
                                />
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="appointments">
                        <EmptyState
                            icon={CalendarDays}
                            title="No appointments yet"
                            description="Appointments booked for this patient will appear here."
                        />
                    </TabsContent>

                    <TabsContent value="visits">
                        <EmptyState
                            icon={Stethoscope}
                            title="No visits recorded"
                            description="Consultation notes from each visit will appear here."
                        />
                    </TabsContent>

                    <TabsContent value="billing">
                        <EmptyState
                            icon={Receipt}
                            title="No invoices yet"
                            description="Invoices and payments for this patient will appear here."
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
