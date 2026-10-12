const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeZone: 'UTC',
});

const timestampFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/**
 * Format a date-only value ("YYYY-MM-DD"), such as a date of birth, without
 * shifting it to the previous day in time zones behind UTC.
 */
export function formatDate(value: string): string {
    return dateFormatter.format(new Date(`${value.slice(0, 10)}T00:00:00Z`));
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
