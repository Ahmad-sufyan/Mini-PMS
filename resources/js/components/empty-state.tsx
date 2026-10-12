import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export default function EmptyState({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-12 text-center">
            <Icon className="size-8 text-muted-foreground" />
            <p className="font-medium">{title}</p>
            <p className="max-w-sm text-sm text-muted-foreground">
                {description}
            </p>
            {children && <div className="mt-2">{children}</div>}
        </div>
    );
}
