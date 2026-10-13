import { useState } from 'react';
import Heading from '@/components/heading';
import Field from '@/components/form-field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { BloodGroup, Gender, Patient, SelectOption } from '@/types';

// Radix Select items can't use an empty value, so "not recorded" gets its own.
const NOT_RECORDED = 'not-recorded';

type Props = {
    genders: SelectOption<Gender>[];
    bloodGroups: SelectOption<BloodGroup>[];
    errors: Partial<Record<string, string>>;
    patient?: Patient;
};

export default function PatientFormFields({
    genders,
    bloodGroups,
    errors,
    patient,
}: Props) {
    const [gender, setGender] = useState<string>(patient?.gender ?? '');
    const [bloodGroup, setBloodGroup] = useState<string>(
        patient?.blood_group ?? NOT_RECORDED,
    );
    const today = new Date().toISOString().slice(0, 10);

    return (
        <>
            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Personal details"
                    description="As they appear on the patient's ID"
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        id="first_name"
                        label="First name"
                        error={errors.first_name}
                    >
                        <Input
                            id="first_name"
                            name="first_name"
                            defaultValue={patient?.first_name}
                            required
                            autoComplete="off"
                            aria-invalid={errors.first_name ? true : undefined}
                        />
                    </Field>

                    <Field
                        id="last_name"
                        label="Last name"
                        error={errors.last_name}
                    >
                        <Input
                            id="last_name"
                            name="last_name"
                            defaultValue={patient?.last_name}
                            required
                            autoComplete="off"
                            aria-invalid={errors.last_name ? true : undefined}
                        />
                    </Field>

                    <Field
                        id="date_of_birth"
                        label="Date of birth"
                        error={errors.date_of_birth}
                    >
                        <Input
                            id="date_of_birth"
                            type="date"
                            name="date_of_birth"
                            defaultValue={patient?.date_of_birth}
                            max={today}
                            required
                            aria-invalid={
                                errors.date_of_birth ? true : undefined
                            }
                        />
                    </Field>

                    <Field id="gender" label="Gender" error={errors.gender}>
                        <input type="hidden" name="gender" value={gender} />
                        <Select value={gender} onValueChange={setGender}>
                            <SelectTrigger
                                id="gender"
                                className="w-full"
                                aria-invalid={errors.gender ? true : undefined}
                            >
                                <SelectValue placeholder="Select gender" />
                            </SelectTrigger>
                            <SelectContent>
                                {genders.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field
                        id="blood_group"
                        label="Blood group"
                        optional
                        error={errors.blood_group}
                    >
                        <input
                            type="hidden"
                            name="blood_group"
                            value={
                                bloodGroup === NOT_RECORDED ? '' : bloodGroup
                            }
                        />
                        <Select
                            value={bloodGroup}
                            onValueChange={setBloodGroup}
                        >
                            <SelectTrigger
                                id="blood_group"
                                className="w-full"
                                aria-invalid={
                                    errors.blood_group ? true : undefined
                                }
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NOT_RECORDED}>
                                    Not recorded
                                </SelectItem>
                                {bloodGroups.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                </div>
            </section>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Contact"
                    description="How the clinic can reach the patient"
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field id="phone" label="Phone" error={errors.phone}>
                        <Input
                            id="phone"
                            type="tel"
                            name="phone"
                            defaultValue={patient?.phone}
                            required
                            autoComplete="off"
                            placeholder="+1 555 0100"
                            aria-invalid={errors.phone ? true : undefined}
                        />
                    </Field>

                    <Field
                        id="email"
                        label="Email address"
                        optional
                        error={errors.email}
                    >
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            defaultValue={patient?.email ?? ''}
                            autoComplete="off"
                            placeholder="email@example.com"
                            aria-invalid={errors.email ? true : undefined}
                        />
                    </Field>
                </div>

                <Field id="address" label="Address" error={errors.address}>
                    <Input
                        id="address"
                        name="address"
                        defaultValue={patient?.address}
                        required
                        autoComplete="off"
                        placeholder="Street, city"
                        aria-invalid={errors.address ? true : undefined}
                    />
                </Field>
            </section>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Emergency contact"
                    description="Who to call if something goes wrong"
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field
                        id="emergency_contact_name"
                        label="Name"
                        error={errors.emergency_contact_name}
                    >
                        <Input
                            id="emergency_contact_name"
                            name="emergency_contact_name"
                            defaultValue={patient?.emergency_contact_name}
                            required
                            autoComplete="off"
                            aria-invalid={
                                errors.emergency_contact_name ? true : undefined
                            }
                        />
                    </Field>

                    <Field
                        id="emergency_contact_phone"
                        label="Phone"
                        error={errors.emergency_contact_phone}
                    >
                        <Input
                            id="emergency_contact_phone"
                            type="tel"
                            name="emergency_contact_phone"
                            defaultValue={patient?.emergency_contact_phone}
                            required
                            autoComplete="off"
                            placeholder="+1 555 0100"
                            aria-invalid={
                                errors.emergency_contact_phone
                                    ? true
                                    : undefined
                            }
                        />
                    </Field>
                </div>
            </section>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Medical"
                    description="Shown to every member of staff who opens the record"
                />

                <Field
                    id="allergies"
                    label="Allergies"
                    optional
                    error={errors.allergies}
                >
                    <Textarea
                        id="allergies"
                        name="allergies"
                        defaultValue={patient?.allergies ?? ''}
                        placeholder="e.g. Penicillin, peanuts. Leave blank if none are known."
                        aria-invalid={errors.allergies ? true : undefined}
                    />
                </Field>

                <Field id="notes" label="Notes" optional error={errors.notes}>
                    <Textarea
                        id="notes"
                        name="notes"
                        defaultValue={patient?.notes ?? ''}
                        aria-invalid={errors.notes ? true : undefined}
                    />
                </Field>
            </section>
        </>
    );
}
