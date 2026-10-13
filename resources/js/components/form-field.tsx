import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * A labelled form control with an optional hint and its validation error.
 */
export default function FormField({
    id,
    label,
    optional = false,
    error,
    hint,
    className,
    children,
}: {
    id: string;
    label: ReactNode;
    optional?: boolean;
    error?: string;
    hint?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        {' '}
                        (optional)
                    </span>
                )}
            </Label>
            {children}
            {hint && <p className="text-sm text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
