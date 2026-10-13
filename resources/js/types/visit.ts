import type { Doctor } from '@/types/appointment';

export type PrescriptionItem = {
    id?: number;
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string;
    instructions: string | null;
};

export type Vitals = {
    bp_systolic: number | null;
    bp_diastolic: number | null;
    pulse: number | null;
    temperature: number | null;
    weight_kg: number | null;
    height_cm: number | null;
    bmi: number | null;
    bmi_category: string | null;
};

type VisitSummary = {
    id: number;
    appointment_id: number | null;
    date: string;
    time: string;
    doctor: Doctor;
    follow_up_date: string | null;
    can_edit: boolean;
};

/**
 * A visit as given by VisitResource. The clinical details are only present
 * when the backend sent them (clinical: true); for staff without clinical
 * access they are not part of the response at all.
 */
export type Visit =
    | (VisitSummary & { clinical: false })
    | (VisitSummary & {
          clinical: true;
          chief_complaint: string;
          diagnosis: string;
          clinical_notes: string | null;
          vitals: Vitals;
          prescriptions: PrescriptionItem[];
      });

export type ClinicalVisit = Extract<Visit, { clinical: true }>;

/**
 * The patient details shown on the visit form and the prescription.
 */
export type VisitPatient = {
    id: number;
    mrn: string;
    full_name: string;
    age: number;
    gender: string;
    date_of_birth: string;
    allergies: string | null;
};
