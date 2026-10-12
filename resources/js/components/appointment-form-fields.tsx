import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import PatientPicker from '@/components/patient-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatOpeningHours } from '@/lib/clinic';
import type {
    Appointment,
    ClinicSchedule,
    Doctor,
    PatientSummary,
} from '@/types';

type Props = {
    doctors: Doctor[];
    durations: number[];
    clinic: ClinicSchedule;
    errors: Partial<Record<string, string>>;
    patient: PatientSummary | null;
    appointment?: Appointment;
    defaultDate?: string;
};

function Field({
    id,
    label,
    optional = false,
    error,
    children,
}: {
    id: string;
    label: string;
    optional?: boolean;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>
                {label}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        {' '}
                        (optional)
                    </span>
                )}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

export default function AppointmentFormFields({
    doctors,
    durations,
    clinic,
    errors,
    patient,
    appointment,
    defaultDate,
}: Props) {
    const [doctor, setDoctor] = useState(
        appointment ? String(appointment.doctor.id) : '',
    );
    const [duration, setDuration] = useState(
        String(appointment?.duration_minutes ?? 30),
    );

    return (
        <>
            <Field id="patient" label="Patient" error={errors.patient_id}>
                <PatientPicker
                    id="patient"
                    defaultPatient={patient}
                    invalid={Boolean(errors.patient_id)}
                />
            </Field>

            <Field id="doctor_id" label="Doctor" error={errors.doctor_id}>
                <input type="hidden" name="doctor_id" value={doctor} />
                <Select value={doctor} onValueChange={setDoctor}>
                    <SelectTrigger
                        id="doctor_id"
                        className="w-full"
                        aria-invalid={errors.doctor_id ? true : undefined}
                    >
                        <SelectValue placeholder="Select a doctor" />
                    </SelectTrigger>
                    <SelectContent>
                        {doctors.map((option) => (
                            <SelectItem
                                key={option.id}
                                value={String(option.id)}
                            >
                                {option.name}
                                {option.specialization && (
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {option.specialization}
                                    </span>
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </Field>

            <div className="space-y-2">
                <div className="grid gap-4 sm:grid-cols-3">
                    <Field id="date" label="Date" error={errors.date}>
                        <Input
                            id="date"
                            type="date"
                            name="date"
                            defaultValue={appointment?.date ?? defaultDate}
                            required
                            aria-invalid={errors.date ? true : undefined}
                        />
                    </Field>

                    <Field id="time" label="Time" error={errors.time}>
                        <Input
                            id="time"
                            type="time"
                            name="time"
                            defaultValue={appointment?.start_time}
                            min={clinic.opens_at}
                            max={clinic.closes_at}
                            step={300}
                            required
                            aria-invalid={errors.time ? true : undefined}
                        />
                    </Field>

                    <Field
                        id="duration_minutes"
                        label="Duration"
                        error={errors.duration_minutes}
                    >
                        <input
                            type="hidden"
                            name="duration_minutes"
                            value={duration}
                        />
                        <Select value={duration} onValueChange={setDuration}>
                            <SelectTrigger
                                id="duration_minutes"
                                className="w-full"
                                aria-invalid={
                                    errors.duration_minutes ? true : undefined
                                }
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {durations.map((minutes) => (
                                    <SelectItem
                                        key={minutes}
                                        value={String(minutes)}
                                    >
                                        {minutes} minutes
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                </div>
                <p className="text-sm text-muted-foreground">
                    Clinic hours: {formatOpeningHours(clinic)}
                </p>
            </div>

            <Field id="reason" label="Reason for visit" error={errors.reason}>
                <Input
                    id="reason"
                    name="reason"
                    defaultValue={appointment?.reason}
                    required
                    autoComplete="off"
                    placeholder="e.g. Follow-up visit"
                    aria-invalid={errors.reason ? true : undefined}
                />
            </Field>

            <Field id="notes" label="Notes" optional error={errors.notes}>
                <Textarea
                    id="notes"
                    name="notes"
                    defaultValue={appointment?.notes ?? ''}
                    aria-invalid={errors.notes ? true : undefined}
                />
            </Field>
        </>
    );
}
