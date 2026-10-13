import type { Appointment, AppointmentStatus } from '@/types/appointment';

/**
 * Today's appointments, by where they are in the visit.
 */
export type DayStats = {
    total: number;
    waiting: number;
    completed: number;
    to_come: number;
};

export type AdminDashboard = {
    role: 'admin';
    stats: {
        patients: number;
        appointments_today: number;
        completed_today: number;
        /** In minor units. */
        revenue_this_month: number;
        /** In minor units. */
        outstanding: number;
        outstanding_invoices: number;
    };
    appointmentsPerDay: { date: string; count: number }[];
    /** Totals in minor units, by "YYYY-MM". */
    revenuePerMonth: { month: string; total: number }[];
    appointmentsByStatus: {
        status: AppointmentStatus;
        label: string;
        count: number;
    }[];
};

export type DoctorDashboard = {
    role: 'doctor';
    appointments: Appointment[];
    nextAppointmentId: number | null;
    stats: DayStats;
};

export type ReceptionDashboard = {
    role: 'reception';
    appointments: Appointment[];
    stats: DayStats;
    unpaidInvoices: number;
};

export type Dashboard = AdminDashboard | DoctorDashboard | ReceptionDashboard;
