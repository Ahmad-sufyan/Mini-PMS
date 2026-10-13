import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * A headline figure, optionally linking to the page behind it.
 */
export default function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    href,
}: {
    label: string;
    value: string | number;
    hint?: string;
    icon: LucideIcon;
    href?: NonNullable<InertiaLinkProps['href']>;
}) {
    const content = (
        <Card
            className={cn(
                'h-full gap-0 py-4',
                href && 'transition-colors hover:bg-muted/50',
            )}
        >
            <CardContent className="flex items-start justify-between gap-4">
                <div className="min-w-0 space-y-1">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="text-2xl font-semibold tracking-tight tabular-nums">
                        {value}
                    </p>
                    {hint && (
                        <p className="truncate text-xs text-muted-foreground">
                            {hint}
                        </p>
                    )}
                </div>
                <Icon className="size-5 shrink-0 text-muted-foreground" />
            </CardContent>
        </Card>
    );

    return href ? (
        <Link
            href={href}
            className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            {content}
        </Link>
    ) : (
        content
    );
}
