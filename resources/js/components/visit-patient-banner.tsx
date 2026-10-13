import { Link } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatAge, formatDay } from '@/lib/dates';
import { show } from '@/routes/patients';
import type { Appointment, VisitPatient } from '@/types';

/**
 * Who the visit is for, shown above the visit form. Allergies are shown as
 * a warning so they can't be missed when prescribing.
 */
export default function VisitPatientBanner({
    patient,
    appointment,
}: {
    patient: VisitPatient;
    appointment?: Appointment | null;
}) {
    return (
        <Card className="max-w-4xl gap-4 py-4">
            <CardContent className="flex flex-col gap-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            <Link
                                href={show(patient.id)}
                                className="hover:underline"
                            >
                                {patient.full_name}
                            </Link>
                        </h1>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary" className="font-mono">
                                {patient.mrn}
                            </Badge>
                            <Badge variant="outline">
                                {formatAge(patient.age)}
                            </Badge>
                            <Badge variant="outline">{patient.gender}</Badge>
                        </div>
                    </div>

                    {appointment && (
                        <div className="text-sm sm:text-right">
                            <div className="font-medium">
                                {formatDay(appointment.date)},{' '}
                                {appointment.start_time}–{appointment.end_time}
                            </div>
                            <div className="text-muted-foreground">
                                {appointment.reason}
                            </div>
                        </div>
                    )}
                </div>

                {patient.allergies && (
                    <Alert className="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        <TriangleAlert />
                        <AlertTitle>Allergies</AlertTitle>
                        <AlertDescription className="text-amber-900 dark:text-amber-200">
                            {patient.allergies}
                        </AlertDescription>
                    </Alert>
                )}
            </CardContent>
        </Card>
    );
}
