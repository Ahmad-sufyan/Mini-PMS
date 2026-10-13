import { Link } from '@inertiajs/react';
import {
    ChevronDown,
    Lock,
    Pencil,
    Plus,
    Printer,
    Stethoscope,
} from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDay, formatLongDay } from '@/lib/dates';
import { describeVitals } from '@/lib/vitals';
import { create, edit, prescription } from '@/routes/visits';
import type { ClinicalVisit, Patient, Visit } from '@/types';

function VisitHeading({ visit }: { visit: Visit }) {
    return (
        <div className="text-sm text-muted-foreground">
            {formatLongDay(visit.date)} at {visit.time} · {visit.doctor.name}
            {visit.doctor.specialization && ` (${visit.doctor.specialization})`}
        </div>
    );
}

function FollowUp({ date }: { date: string | null }) {
    if (!date) {
        return null;
    }

    return (
        <p className="text-sm">
            <span className="text-muted-foreground">Follow-up due: </span>
            <span className="font-medium">{formatDay(date)}</span>
        </p>
    );
}

/**
 * A visit as seen by staff without clinical access: when it happened, who
 * saw the patient and when they should come back. That's all the backend
 * sends them.
 */
function VisitSummary({ visit }: { visit: Visit }) {
    return (
        <div className="space-y-1 rounded-xl border p-4">
            <VisitHeading visit={visit} />
            <FollowUp date={visit.follow_up_date} />
        </div>
    );
}

function ClinicalVisitEntry({
    visit,
    defaultOpen,
}: {
    visit: ClinicalVisit;
    defaultOpen: boolean;
}) {
    return (
        <Collapsible defaultOpen={defaultOpen} className="rounded-xl border">
            <CollapsibleTrigger asChild>
                <button
                    type="button"
                    className="group flex w-full items-start justify-between gap-4 rounded-xl p-4 text-left hover:bg-muted/50"
                    data-test="visit-toggle"
                >
                    <div className="min-w-0 space-y-1">
                        <VisitHeading visit={visit} />
                        <div className="font-medium">{visit.diagnosis}</div>
                        <div className="text-sm text-muted-foreground">
                            {visit.chief_complaint}
                        </div>
                    </div>
                    <ChevronDown className="mt-1 size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                </button>
            </CollapsibleTrigger>

            <CollapsibleContent className="space-y-5 border-t p-4">
                <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3 lg:grid-cols-6">
                    {describeVitals(visit.vitals).map((vital) => (
                        <div key={vital.label}>
                            <dt className="text-muted-foreground">
                                {vital.label}
                            </dt>
                            <dd className="font-medium">{vital.value}</dd>
                        </div>
                    ))}
                </dl>

                {visit.clinical_notes && (
                    <div className="space-y-1">
                        <h4 className="text-sm font-medium">Clinical notes</h4>
                        <p className="text-sm whitespace-pre-line">
                            {visit.clinical_notes}
                        </p>
                    </div>
                )}

                <div className="space-y-2">
                    <h4 className="text-sm font-medium">Prescription</h4>
                    {visit.prescriptions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No medicines prescribed.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border text-sm">
                            {visit.prescriptions.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex flex-wrap gap-x-2 px-3 py-2"
                                >
                                    <span className="font-medium">
                                        {item.medicine} {item.dosage}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {item.frequency}, {item.duration}
                                        {item.instructions &&
                                            ` · ${item.instructions}`}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <FollowUp date={visit.follow_up_date} />
                    <div className="ml-auto flex gap-2">
                        {visit.can_edit && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={edit(visit.id)}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        )}
                        {visit.prescriptions.length > 0 && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={prescription.url(visit.id)}
                                    target="_blank"
                                    rel="noopener"
                                    data-test="print-prescription-link"
                                >
                                    <Printer />
                                    Print prescription
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * The Visits tab of the patient page: a timeline of visits, newest first.
 */
export default function PatientVisits({
    patient,
    visits,
}: {
    patient: Patient;
    visits: Visit[];
}) {
    const { can } = usePermissions();
    const canRecord = can('visits.record') && patient.deleted_at === null;
    const restricted = visits.some((visit) => !visit.clinical);

    const recordButton = canRecord && (
        <Button variant="outline" asChild>
            <Link
                href={create({ query: { patient: patient.id } })}
                data-test="record-visit-button"
            >
                <Plus />
                Record walk-in visit
            </Link>
        </Button>
    );

    if (visits.length === 0) {
        return (
            <EmptyState
                icon={Stethoscope}
                title="No visits recorded"
                description="Consultation notes from each visit will appear here."
            >
                {recordButton}
            </EmptyState>
        );
    }

    return (
        <div className="space-y-4">
            {(restricted || recordButton) && (
                <div className="flex flex-wrap items-center justify-between gap-2">
                    {restricted ? (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Lock className="size-4" />
                            Diagnoses, notes and prescriptions are only
                            available to clinical staff.
                        </p>
                    ) : (
                        <span />
                    )}
                    {recordButton}
                </div>
            )}

            <ol className="relative ml-2 space-y-4 border-l pl-6">
                {visits.map((visit, index) => (
                    <li key={visit.id} className="relative">
                        <span
                            className="absolute top-5 -left-[31px] size-3 rounded-full border-2 border-background bg-primary"
                            aria-hidden
                        />
                        {visit.clinical ? (
                            <ClinicalVisitEntry
                                visit={visit}
                                defaultOpen={index === 0}
                            />
                        ) : (
                            <VisitSummary visit={visit} />
                        )}
                    </li>
                ))}
            </ol>
        </div>
    );
}
