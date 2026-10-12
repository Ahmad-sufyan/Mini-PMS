import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { AppointmentStatus } from '@/types';

/**
 * Colours for each status, shared by the badge and the week view.
 */
export const appointmentStatusStyles: Record<AppointmentStatus, string> = {
    scheduled:
        'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200',
    checked_in:
        'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200',
    completed:
        'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200',
    cancelled:
        'border-neutral-300 bg-neutral-100 text-neutral-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
    no_show:
        'border-red-300 bg-red-50 text-red-900 dark:border-red-500/40 dark:bg-red-500/15 dark:text-red-200',
};

export default function AppointmentStatusBadge({
    status,
    label,
    className,
}: {
    status: AppointmentStatus;
    label: string;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(appointmentStatusStyles[status], className)}
        >
            {label}
        </Badge>
    );
}
