import { Card, CardContent } from '@/components/ui/card';
import { useCurrency } from '@/hooks/use-currency';
import { cn } from '@/lib/utils';
import type { InvoiceTotals as Totals } from '@/types';

/**
 * Billed, collected and outstanding totals (void invoices don't count).
 */
export default function InvoiceTotals({ totals }: { totals: Totals }) {
    const { format } = useCurrency();

    const cards = [
        { label: 'Billed', value: totals.billed, tone: '' },
        {
            label: 'Collected',
            value: totals.collected,
            tone: 'text-emerald-700 dark:text-emerald-400',
        },
        {
            label: 'Outstanding',
            value: totals.outstanding,
            tone:
                totals.outstanding > 0
                    ? 'text-amber-700 dark:text-amber-400'
                    : '',
        },
    ];

    return (
        <div className="grid gap-4 sm:grid-cols-3">
            {cards.map((card) => (
                <Card key={card.label} className="gap-1 py-4">
                    <CardContent>
                        <div className="text-sm text-muted-foreground">
                            {card.label}
                        </div>
                        <div
                            className={cn(
                                'text-2xl font-semibold tabular-nums',
                                card.tone,
                            )}
                            data-test={`total-${card.label.toLowerCase()}`}
                        >
                            {format(card.value)}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
