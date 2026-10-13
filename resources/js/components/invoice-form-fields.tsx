import Field from '@/components/form-field';
import Heading from '@/components/heading';
import InvoiceItemsEditor from '@/components/invoice-items-editor';
import PatientPicker from '@/components/patient-picker';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatAge, formatDay } from '@/lib/dates';
import type {
    InvoiceItem,
    InvoiceVisit,
    PatientSummary,
    Service,
} from '@/types';

type Props = {
    services: Service[];
    errors: Partial<Record<string, string>>;
    items: InvoiceItem[];
    discount?: number;
    issuedOn: string;
    maxDate: string;
    notes?: string | null;
    /** The patient being billed; picked in the form when null. */
    patient:
        | PatientSummary
        | { id: number; mrn: string; full_name: string }
        | null;
    /** Whether the patient can be changed (only when creating, not from a visit). */
    choosePatient?: boolean;
    visit?: InvoiceVisit | null;
};

export default function InvoiceFormFields({
    services,
    errors,
    items,
    discount = 0,
    issuedOn,
    maxDate,
    notes,
    patient,
    choosePatient = false,
    visit,
}: Props) {
    return (
        <>
            <section className="space-y-4">
                <Heading variant="small" title="Patient" />

                {choosePatient ? (
                    <Field
                        id="patient"
                        label="Patient"
                        error={errors.patient_id}
                    >
                        <PatientPicker
                            id="patient"
                            defaultPatient={
                                patient && 'age' in patient ? patient : null
                            }
                            invalid={Boolean(errors.patient_id)}
                        />
                    </Field>
                ) : (
                    patient && (
                        <div className="rounded-md border px-3 py-2">
                            <div className="font-medium">
                                {patient.full_name}
                            </div>
                            <div className="text-sm text-muted-foreground">
                                {patient.mrn}
                                {'age' in patient &&
                                    ` · ${formatAge(patient.age)}`}
                            </div>
                        </div>
                    )
                )}

                {visit && (
                    <>
                        <input type="hidden" name="visit_id" value={visit.id} />
                        <p className="text-sm text-muted-foreground">
                            For the visit with {visit.doctor} on{' '}
                            {formatDay(visit.date)}.
                        </p>
                    </>
                )}

                {!choosePatient && !visit && patient && (
                    <input type="hidden" name="patient_id" value={patient.id} />
                )}

                <Field
                    id="issued_on"
                    label="Invoice date"
                    error={errors.issued_on}
                    className="max-w-48"
                >
                    <Input
                        id="issued_on"
                        type="date"
                        name="issued_on"
                        defaultValue={issuedOn}
                        max={maxDate}
                        required
                        aria-invalid={errors.issued_on ? true : undefined}
                    />
                </Field>
            </section>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Lines"
                    description="Pick a service from the price list or type a custom line"
                />

                <InvoiceItemsEditor
                    services={services}
                    items={items}
                    discount={discount}
                    errors={errors}
                />
            </section>

            <Field id="notes" label="Notes" optional error={errors.notes}>
                <Textarea
                    id="notes"
                    name="notes"
                    defaultValue={notes ?? ''}
                    placeholder="Printed on the invoice"
                    aria-invalid={errors.notes ? true : undefined}
                />
            </Field>
        </>
    );
}
