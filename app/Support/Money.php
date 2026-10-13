<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Money is stored as integers in the currency's smallest unit (e.g. cents),
 * in the clinic's currency from config/clinic.php.
 */
class Money
{
    /**
     * ISO 4217 currencies without a minor unit.
     *
     * @var list<string>
     */
    private const array ZERO_DECIMALS = [
        'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG',
        'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    /**
     * ISO 4217 currencies with three decimals.
     *
     * @var list<string>
     */
    private const array THREE_DECIMALS = ['BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'];

    /**
     * Get the clinic's currency code.
     */
    public static function currency(): string
    {
        return strtoupper(Config::string('clinic.currency'));
    }

    /**
     * Get the number of decimals (minor unit digits) of a currency.
     */
    public static function decimals(?string $currency = null): int
    {
        $currency ??= self::currency();

        return match (true) {
            in_array($currency, self::ZERO_DECIMALS, true) => 0,
            in_array($currency, self::THREE_DECIMALS, true) => 3,
            default => 2,
        };
    }

    /**
     * Convert an amount typed into a form ("12.50") to minor units (1250).
     * The string is split rather than multiplied as a float, so no cents are
     * lost to rounding. Validate it with the MoneyAmount rule first.
     */
    public static function toMinor(string $amount): int
    {
        $decimals = self::decimals();
        [$whole, $fraction] = array_pad(explode('.', trim($amount), 2), 2, '');

        return (int) $whole * 10 ** $decimals
            + (int) str_pad(substr($fraction, 0, $decimals), $decimals, '0');
    }

    /**
     * Format minor units for a message, e.g. "USD 12.50". Pages format
     * amounts in the browser with Intl.NumberFormat instead.
     */
    public static function format(int $minor): string
    {
        $decimals = self::decimals();

        return self::currency().' '.number_format($minor / 10 ** $decimals, $decimals);
    }

    /**
     * Get the currency details shared with the frontend.
     *
     * @return array{code: string, decimals: int}
     */
    public static function toArray(): array
    {
        return [
            'code' => self::currency(),
            'decimals' => self::decimals(),
        ];
    }
}
