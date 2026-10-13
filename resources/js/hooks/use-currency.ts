import { usePage } from '@inertiajs/react';
import { formatMoney } from '@/lib/money';
import type { Currency } from '@/types';

export type UseCurrencyReturn = {
    currency: Currency;
    format: (minor: number) => string;
};

/**
 * The clinic's currency (shared by HandleInertiaRequests) and a formatter
 * for amounts in minor units.
 */
export function useCurrency(): UseCurrencyReturn {
    const { currency } = usePage().props;

    return {
        currency,
        format: (minor: number) => formatMoney(minor, currency),
    };
}
