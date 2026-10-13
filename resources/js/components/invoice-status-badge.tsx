import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { InvoiceStatus } from '@/types';

const styles: Record<InvoiceStatus, string> = {
    unpaid: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200',
    partial:
        'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200',
    paid: 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200',
    void: 'border-neutral-300 bg-neutral-100 text-neutral-500 line-through dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
};

export default function InvoiceStatusBadge({
    status,
    label,
    className,
}: {
    status: InvoiceStatus;
    label: string;
    className?: string;
}) {
    return (
        <Badge variant="outline" className={cn(styles[status], className)}>
            {label}
        </Badge>
    );
}
