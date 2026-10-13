import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatAge, formatDate, formatLongDay } from '@/lib/dates';
import { show } from '@/routes/patients';
import type { ClinicalVisit, VisitPatient } from '@/types';

type Props = {
    visit: ClinicalVisit;
    patient: VisitPatient;
    clinic: {
        name: string;
        address: string;
        phone: string;
    };
};

/**
 * A printable prescription (A4). It renders without the app layout, and the
 * toolbar is hidden when printing; the browser's print dialog does the rest.
 */
export default function Prescription({ visit, patient, clinic }: Props) {
    return (
        <>
            <Head title={`Prescription for ${patient.full_name}`} />

            <div className="min-h-screen bg-neutral-100 py-8 dark:bg-neutral-900 print:min-h-0 print:bg-white print:py-0">
                <div className="mx-auto mb-4 flex max-w-[210mm] items-center justify-between gap-4 px-4 print:hidden">
                    <Button variant="ghost" asChild>
                        <Link
                            href={show(patient.id, {
                                query: { tab: 'visits' },
                            })}
                        >
                            <ArrowLeft />
                            Back to {patient.full_name}
                        </Link>
                    </Button>
                    <Button
                        onClick={() => window.print()}
                        data-test="print-prescription-button"
                    >
                        <Printer />
                        Print
                    </Button>
                </div>

                <article className="mx-auto max-w-[210mm] bg-white p-[16mm] text-sm text-neutral-900 shadow-md print:max-w-none print:p-0 print:shadow-none">
                    <header className="flex items-start justify-between gap-6 border-b-2 border-neutral-900 pb-4">
                        <div>
                            <h1 className="text-xl font-bold">{clinic.name}</h1>
                            <p>{clinic.address}</p>
                            <p>Tel: {clinic.phone}</p>
                        </div>
                        <div className="text-right">
                            <p className="font-semibold">{visit.doctor.name}</p>
                            {visit.doctor.specialization && (
                                <p>{visit.doctor.specialization}</p>
                            )}
                        </div>
                    </header>

                    <section className="mt-6 grid grid-cols-2 gap-x-8 gap-y-1">
                        <p>
                            <span className="text-neutral-500">Patient: </span>
                            <span className="font-semibold">
                                {patient.full_name}
                            </span>
                        </p>
                        <p className="text-right">
                            <span className="text-neutral-500">Date: </span>
                            {formatLongDay(visit.date)}
                        </p>
                        <p>
                            <span className="text-neutral-500">MRN: </span>
                            {patient.mrn}
                        </p>
                        <p className="text-right">
                            <span className="text-neutral-500">
                                Age / gender:{' '}
                            </span>
                            {formatAge(patient.age)}, {patient.gender}
                        </p>
                        <p>
                            <span className="text-neutral-500">
                                Date of birth:{' '}
                            </span>
                            {formatDate(patient.date_of_birth)}
                        </p>
                    </section>

                    {patient.allergies && (
                        <p className="mt-4 rounded border border-neutral-900 px-3 py-2">
                            <span className="font-semibold">Allergies: </span>
                            {patient.allergies}
                        </p>
                    )}

                    <p className="mt-4">
                        <span className="font-semibold">Diagnosis: </span>
                        {visit.diagnosis}
                    </p>

                    <p
                        className="mt-8 font-serif text-4xl leading-none font-bold"
                        aria-label="Prescription"
                    >
                        ℞
                    </p>

                    {visit.prescriptions.length === 0 ? (
                        <p className="mt-4 text-neutral-500">
                            No medicines were prescribed at this visit.
                        </p>
                    ) : (
                        <table className="mt-3 w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b-2 border-neutral-900">
                                    <th className="py-2 pr-2 font-semibold">
                                        #
                                    </th>
                                    <th className="py-2 pr-2 font-semibold">
                                        Medicine
                                    </th>
                                    <th className="py-2 pr-2 font-semibold">
                                        Dosage
                                    </th>
                                    <th className="py-2 pr-2 font-semibold">
                                        Frequency
                                    </th>
                                    <th className="py-2 pr-2 font-semibold">
                                        Duration
                                    </th>
                                    <th className="py-2 font-semibold">
                                        Instructions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {visit.prescriptions.map((item, index) => (
                                    <tr
                                        key={item.id ?? index}
                                        className="break-inside-avoid border-b border-neutral-300 align-top"
                                    >
                                        <td className="py-2 pr-2">
                                            {index + 1}
                                        </td>
                                        <td className="py-2 pr-2 font-medium">
                                            {item.medicine}
                                        </td>
                                        <td className="py-2 pr-2">
                                            {item.dosage}
                                        </td>
                                        <td className="py-2 pr-2">
                                            {item.frequency}
                                        </td>
                                        <td className="py-2 pr-2">
                                            {item.duration}
                                        </td>
                                        <td className="py-2">
                                            {item.instructions ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}

                    {visit.follow_up_date && (
                        <p className="mt-6">
                            <span className="font-semibold">Follow-up: </span>
                            {formatLongDay(visit.follow_up_date)}
                        </p>
                    )}

                    <footer className="mt-20 flex break-inside-avoid justify-end">
                        <div className="w-60 border-t border-neutral-900 pt-1 text-center">
                            <p className="font-medium">{visit.doctor.name}</p>
                            <p className="text-neutral-500">Signature</p>
                        </div>
                    </footer>
                </article>
            </div>
        </>
    );
}
