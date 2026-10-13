import type { ClinicSchedule } from '@/types';

const WEEK = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

const capitalize = (value: string): string =>
    value.charAt(0).toUpperCase() + value.slice(1);

/**
 * Convert a time of day ("HH:mm") to minutes after midnight.
 */
export function timeToMinutes(time: string): number {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

/**
 * Describe the working days, e.g. "Mon–Sat" or "Mon, Wed, Fri".
 */
export function formatWorkingDays(days: string[]): string {
    const indexes = days
        .map((day) => WEEK.indexOf(day))
        .filter((index) => index !== -1)
        .sort((a, b) => a - b);

    if (indexes.length === 0) {
        return 'No working days';
    }

    const consecutive = indexes.every(
        (index, position) =>
            position === 0 || index === indexes[position - 1] + 1,
    );

    if (consecutive && indexes.length > 2) {
        return `${capitalize(WEEK[indexes[0]])}–${capitalize(WEEK[indexes[indexes.length - 1]])}`;
    }

    return indexes.map((index) => capitalize(WEEK[index])).join(', ');
}

/**
 * Describe the opening hours, e.g. "Mon–Sat, 09:00–17:00 (UTC)".
 */
export function formatOpeningHours(clinic: ClinicSchedule): string {
    return `${formatWorkingDays(clinic.working_days)}, ${clinic.opens_at}–${clinic.closes_at} (${clinic.timezone})`;
}
