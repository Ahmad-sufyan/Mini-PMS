import { useState } from 'react';
import type { ComponentProps } from 'react';
import Field from '@/components/form-field';
import Heading from '@/components/heading';
import PrescriptionRows from '@/components/prescription-rows';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { bmiCategory, calculateBmi } from '@/lib/vitals';
import type { ClinicalVisit } from '@/types';

type Props = {
    visit?: ClinicalVisit;
    defaultComplaint?: string;
    minFollowUp: string;
    errors: Partial<Record<string, string>>;
    frequencies: string[];
    durations: string[];
};

/**
 * A number input with its unit shown inside the field.
 */
function UnitInput({
    unit,
    ...props
}: ComponentProps<typeof Input> & { unit: string }) {
    return (
        <div className="relative">
            <Input
                type="number"
                inputMode="decimal"
                className="pr-14"
                {...props}
            />
            <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted-foreground">
                {unit}
            </span>
        </div>
    );
}

export default function VisitFormFields({
    visit,
    defaultComplaint,
    minFollowUp,
    errors,
    frequencies,
    durations,
}: Props) {
    const vitals = visit?.vitals;
    const [weight, setWeight] = useState(vitals?.weight_kg?.toString() ?? '');
    const [height, setHeight] = useState(vitals?.height_cm?.toString() ?? '');
    const bmi = calculateBmi(Number(weight), Number(height));

    const invalid = (field: string) => (errors[field] ? true : undefined);

    return (
        <>
            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Vitals"
                    description="Leave blank anything that wasn't measured"
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        id="bp_systolic"
                        label="Blood pressure"
                        error={errors.bp_systolic ?? errors.bp_diastolic}
                    >
                        <div className="flex items-center gap-2">
                            <Input
                                id="bp_systolic"
                                type="number"
                                name="bp_systolic"
                                defaultValue={vitals?.bp_systolic ?? ''}
                                placeholder="120"
                                aria-label="Systolic pressure"
                                aria-invalid={invalid('bp_systolic')}
                            />
                            <span className="text-muted-foreground">/</span>
                            <UnitInput
                                name="bp_diastolic"
                                unit="mmHg"
                                defaultValue={vitals?.bp_diastolic ?? ''}
                                placeholder="80"
                                aria-label="Diastolic pressure"
                                aria-invalid={invalid('bp_diastolic')}
                            />
                        </div>
                    </Field>

                    <Field id="pulse" label="Pulse" error={errors.pulse}>
                        <UnitInput
                            id="pulse"
                            name="pulse"
                            unit="bpm"
                            defaultValue={vitals?.pulse ?? ''}
                            aria-invalid={invalid('pulse')}
                        />
                    </Field>

                    <Field
                        id="temperature"
                        label="Temperature"
                        error={errors.temperature}
                    >
                        <UnitInput
                            id="temperature"
                            name="temperature"
                            unit="°C"
                            step="0.1"
                            defaultValue={vitals?.temperature ?? ''}
                            aria-invalid={invalid('temperature')}
                        />
                    </Field>

                    <Field
                        id="weight_kg"
                        label="Weight"
                        error={errors.weight_kg}
                    >
                        <UnitInput
                            id="weight_kg"
                            name="weight_kg"
                            unit="kg"
                            step="0.1"
                            value={weight}
                            onChange={(event) => setWeight(event.target.value)}
                            aria-invalid={invalid('weight_kg')}
                        />
                    </Field>

                    <Field
                        id="height_cm"
                        label="Height"
                        error={errors.height_cm}
                    >
                        <UnitInput
                            id="height_cm"
                            name="height_cm"
                            unit="cm"
                            step="0.1"
                            value={height}
                            onChange={(event) => setHeight(event.target.value)}
                            aria-invalid={invalid('height_cm')}
                        />
                    </Field>

                    <div className="grid gap-2">
                        <span className="text-sm font-medium">BMI</span>
                        <div
                            className="flex h-9 items-center rounded-md border border-dashed px-3 text-sm"
                            aria-live="polite"
                            data-test="bmi"
                        >
                            {bmi === null ? (
                                <span className="text-muted-foreground">
                                    From weight and height
                                </span>
                            ) : (
                                <>
                                    <span className="font-medium">{bmi}</span>
                                    <span className="ml-2 text-muted-foreground">
                                        {bmiCategory(bmi)}
                                    </span>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <section className="space-y-4">
                <Heading variant="small" title="Consultation" />

                <Field
                    id="chief_complaint"
                    label="Chief complaint"
                    error={errors.chief_complaint}
                >
                    <Input
                        id="chief_complaint"
                        name="chief_complaint"
                        defaultValue={
                            visit?.chief_complaint ?? defaultComplaint
                        }
                        required
                        autoComplete="off"
                        aria-invalid={invalid('chief_complaint')}
                    />
                </Field>

                <Field
                    id="diagnosis"
                    label="Diagnosis"
                    error={errors.diagnosis}
                >
                    <Textarea
                        id="diagnosis"
                        name="diagnosis"
                        defaultValue={visit?.diagnosis}
                        required
                        aria-invalid={invalid('diagnosis')}
                    />
                </Field>

                <Field
                    id="clinical_notes"
                    label="Clinical notes"
                    optional
                    error={errors.clinical_notes}
                >
                    <Textarea
                        id="clinical_notes"
                        name="clinical_notes"
                        defaultValue={visit?.clinical_notes ?? ''}
                        className="min-h-24"
                        aria-invalid={invalid('clinical_notes')}
                    />
                </Field>

                <Field
                    id="follow_up_date"
                    label="Follow-up date"
                    optional
                    error={errors.follow_up_date}
                    className="max-w-56"
                >
                    <Input
                        id="follow_up_date"
                        type="date"
                        name="follow_up_date"
                        defaultValue={visit?.follow_up_date ?? ''}
                        min={minFollowUp}
                        aria-invalid={invalid('follow_up_date')}
                    />
                </Field>
            </section>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Prescription"
                    description="Printed on the prescription for the patient"
                />

                <PrescriptionRows
                    items={visit?.prescriptions ?? []}
                    errors={errors}
                    frequencies={frequencies}
                    durations={durations}
                />
            </section>
        </>
    );
}
