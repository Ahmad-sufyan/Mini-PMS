import type { Currency } from '@/types';

/**
 * Format an amount in minor units, e.g. 1250 → "$12.50".
 */
export function formatMoney(minor: number, currency: Currency): string {
    const amount = minor / 10 ** currency.decimals;

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currency.code,
            minimumFractionDigits: currency.decimals,
            maximumFractionDigits: currency.decimals,
        }).format(amount);
    } catch {
        // An unknown currency code in the config.
        return `${currency.code} ${amount.toFixed(currency.decimals)}`;
    }
}

/**
 * Turn an amount in minor units into a value for an input, e.g. 1250 → "12.50".
 */
export function toAmountInput(minor: number, currency: Currency): string {
    return (minor / 10 ** currency.decimals).toFixed(currency.decimals);
}

/**
 * Parse an amount typed into an input into minor units, the way the backend
 * does (Money::toMinor()), or null when it isn't a valid amount.
 */
export function parseAmount(value: string, currency: Currency): number | null {
    const trimmed = value.trim();
    const pattern =
        currency.decimals === 0
            ? /^\d+$/
            : new RegExp(`^\\d+(\\.\\d{1,${currency.decimals}})?$`);

    if (!pattern.test(trimmed)) {
        return null;
    }

    const [whole, fraction = ''] = trimmed.split('.');

    return (
        Number(whole) * 10 ** currency.decimals +
        Number(fraction.padEnd(currency.decimals, '0') || 0)
    );
}

/**
 * The step for amount inputs, e.g. "0.01" for a currency with two decimals.
 */
export function amountStep(currency: Currency): string {
    return currency.decimals === 0
        ? '1'
        : (1 / 10 ** currency.decimals).toFixed(currency.decimals);
}
