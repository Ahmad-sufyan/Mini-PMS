import type { Vitals } from '@/types';

/**
 * Calculate the body mass index, as the backend does (Visit::bmi()).
 */
export function calculateBmi(
    weightKg: number,
    heightCm: number,
): number | null {
    if (!weightKg || !heightCm) {
        return null;
    }

    return Math.round((weightKg / (heightCm / 100) ** 2) * 10) / 10;
}

/**
 * Get the adult BMI category, as the backend does (Visit::bmiCategory()).
 */
export function bmiCategory(bmi: number): string {
    if (bmi < 18.5) {
        return 'Underweight';
    }

    if (bmi < 25) {
        return 'Healthy weight';
    }

    return bmi < 30 ? 'Overweight' : 'Obese';
}

const withUnit = (value: number | null, unit: string): string | null =>
    value === null ? null : `${value} ${unit}`;

/**
 * Describe the vitals taken at a visit, with "—" for ones that weren't.
 */
export function describeVitals(
    vitals: Vitals,
): { label: string; value: string }[] {
    const bloodPressure =
        vitals.bp_systolic !== null && vitals.bp_diastolic !== null
            ? `${vitals.bp_systolic}/${vitals.bp_diastolic} mmHg`
            : null;

    const bmi =
        vitals.bmi !== null ? `${vitals.bmi} (${vitals.bmi_category})` : null;

    return [
        { label: 'Blood pressure', value: bloodPressure },
        { label: 'Pulse', value: withUnit(vitals.pulse, 'bpm') },
        { label: 'Temperature', value: withUnit(vitals.temperature, '°C') },
        { label: 'Weight', value: withUnit(vitals.weight_kg, 'kg') },
        { label: 'Height', value: withUnit(vitals.height_cm, 'cm') },
        { label: 'BMI', value: bmi },
    ].map((vital) => ({ label: vital.label, value: vital.value ?? '—' }));
}
