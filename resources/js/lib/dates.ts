const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeZone: 'UTC',
});

const timestampFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    timeZone: 'UTC',
});

const longDayFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});

const toUtcDate = (value: string): Date =>
    new Date(`${value.slice(0, 10)}T00:00:00Z`);

/**
 * Format a date-only value as a short day, e.g. "Mon, 12 Oct".
 */
export function formatDay(value: string): string {
    return dayFormatter.format(toUtcDate(value));
}

/**
 * Format a date-only value as a long day, e.g. "Monday, 12 October 2026".
 */
export function formatLongDay(value: string): string {
    return longDayFormatter.format(toUtcDate(value));
}

/**
 * Add days to a date-only value ("YYYY-MM-DD").
 */
export function addDays(value: string, days: number): string {
    const date = toUtcDate(value);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}

/**
 * Format a date-only value ("YYYY-MM-DD"), such as a date of birth, without
 * shifting it to the previous day in time zones behind UTC.
 */
export function formatDate(value: string): string {
    return dateFormatter.format(toUtcDate(value));
}

/**
 * Format a timestamp as a date in the viewer's time zone.
 */
export function formatTimestamp(value: string): string {
    return timestampFormatter.format(new Date(value));
}

/**
 * Format an age in whole years for display.
 */
export function formatAge(age: number): string {
    if (age < 1) {
        return 'Under 1 year';
    }

    return age === 1 ? '1 year' : `${age} years`;
}
