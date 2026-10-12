import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { TableHead } from '@/components/ui/table';
import { cn } from '@/lib/utils';

const sortIcons = {
    asc: ArrowUp,
    desc: ArrowDown,
    none: ArrowUpDown,
};

const ariaSort = {
    asc: 'ascending',
    desc: 'descending',
    none: 'none',
} as const;

export default function SortableTableHead({
    label,
    href,
    direction,
    className,
}: {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
    direction: 'asc' | 'desc' | null;
    className?: string;
}) {
    const state = direction ?? 'none';
    const Icon = sortIcons[state];

    return (
        <TableHead className={className} aria-sort={ariaSort[state]}>
            <Link
                href={href}
                preserveScroll
                preserveState
                className="-ml-2 inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-muted"
            >
                {label}
                <Icon
                    className={cn(
                        'size-3.5',
                        direction === null && 'text-muted-foreground/60',
                    )}
                />
            </Link>
        </TableHead>
    );
}
