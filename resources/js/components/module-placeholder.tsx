import type { LucideIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';

export default function ModulePlaceholder({
    title,
    description,
    icon: Icon,
}: {
    title: string;
    description: string;
    icon: LucideIcon;
}) {
    return (
        <div className="flex h-full flex-1 flex-col px-4 py-6">
            <Heading title={title} description={description} />

            <div className="relative flex min-h-[60vh] flex-1 items-center justify-center overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />

                <div className="relative flex flex-col items-center gap-2 rounded-lg border bg-background px-8 py-6 text-center shadow-sm">
                    <Icon className="size-8 text-muted-foreground" />
                    <p className="font-medium">Coming soon</p>
                    <p className="text-sm text-muted-foreground">
                        The {title.toLowerCase()} module hasn't been built yet.
                    </p>
                </div>
            </div>
        </div>
    );
}
