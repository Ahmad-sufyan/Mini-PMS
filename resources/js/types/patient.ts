export type Gender = 'male' | 'female' | 'other';

export type BloodGroup =
    | 'A+'
    | 'A-'
    | 'B+'
    | 'B-'
    | 'AB+'
    | 'AB-'
    | 'O+'
    | 'O-';

export type Patient = {
    id: number;
    mrn: string;
    first_name: string;
    last_name: string;
    full_name: string;
    date_of_birth: string;
    age: number;
    gender: Gender;
    phone: string;
    email: string | null;
    address: string;
    blood_group: BloodGroup | null;
    allergies: string | null;
    notes: string | null;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

export type PatientListItem = Pick<
    Patient,
    | 'id'
    | 'mrn'
    | 'full_name'
    | 'date_of_birth'
    | 'age'
    | 'gender'
    | 'phone'
    | 'allergies'
    | 'created_at'
    | 'deleted_at'
>;

/**
 * The few details needed to pick out a patient (PatientSummaryResource).
 */
export type PatientSummary = {
    id: number;
    mrn: string;
    full_name: string;
    age: number;
    phone: string;
};

export type PatientSort = 'name' | 'mrn' | 'age' | 'registered';

export type PatientFilters = {
    search: string;
    gender: Gender | null;
    sort: PatientSort;
    direction: 'asc' | 'desc';
    status: 'active' | 'deleted';
};
