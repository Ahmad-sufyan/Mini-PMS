import { Link } from '@inertiajs/react';
import { CalendarDays, List, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { create, index, week } from '@/routes/appointments';

/**
 * The heading shared by the list and week views, with a switch between
 * them and the booking button.
 */
export default function AppointmentsHeader({
    view,
    doctor,
}: {
    view: 'list' | 'week';
    doctor: number | null;
}) {
    const { can } = usePermissions();
    const query = { doctor: doctor ?? undefined };

    const views = [
        { key: 'list', label: 'List', icon: List, href: index({ query }) },
        {
            key: 'week',
            label: 'Week',
            icon: CalendarDays,
            href: week({ query }),
        },
    ] as const;

    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Appointments"
                description={
                    can('appointments.manage')
                        ? 'Book, reschedule and track appointments'
                        : 'Your appointments with patients'
                }
            />

            <div className="flex items-center gap-2">
                <div
                    className="flex rounded-lg bg-muted p-[3px]"
                    role="group"
                    aria-label="View"
                >
                    {views.map((option) => (
                        <Button
                            key={option.key}
                            variant="ghost"
                            size="sm"
                            asChild
                            className={cn(
                                'h-7',
                                view === option.key &&
                                    'bg-background shadow-sm hover:bg-background',
                            )}
                        >
                            <Link
                                href={option.href}
                                aria-current={
                                    view === option.key ? 'page' : undefined
                                }
                            >
                                <option.icon />
                                {option.label}
                            </Link>
                        </Button>
                    ))}
                </div>

                {can('appointments.manage') && (
                    <Button asChild>
                        <Link
                            href={create()}
                            data-test="book-appointment-button"
                        >
                            <Plus />
                            Book appointment
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
