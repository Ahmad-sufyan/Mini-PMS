export type AppointmentStatus =
    | 'scheduled'
    | 'checked_in'
    | 'completed'
    | 'cancelled'
    | 'no_show';

export type Doctor = {
    id: number;
    name: string;
    specialization: string | null;
};

/**
 * An appointment as given by AppointmentResource. Dates and times are in
 * the clinic's timezone.
 */
export type Appointment = {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    status: AppointmentStatus;
    status_label: string;
    reason: string;
    notes: string | null;
    patient: {
        id: number;
        mrn: string;
        full_name: string;
    };
    doctor: Doctor;
    next_statuses: {
        value: AppointmentStatus;
        label: string;
    }[];
    can_edit: boolean;
};

export type AppointmentFilters = {
    from: string;
    to: string;
    doctor: number | null;
    status: AppointmentStatus | null;
};

export type WeekDay = {
    date: string;
    is_working_day: boolean;
    is_today: boolean;
};

export type Week = {
    start: string;
    previous: string;
    next: string;
    days: WeekDay[];
};
