<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoiceStatus: string
{
    use HasOptions;

    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Void = 'void';

    /**
     * Get the human readable name of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partly paid',
            self::Paid => 'Paid',
            self::Void => 'Void',
        };
    }

    /**
     * Get the status an invoice should have once the given amount has been
     * paid towards its total (both in minor units).
     */
    public static function fromPayments(int $paid, int $total): self
    {
        return match (true) {
            $paid >= $total => self::Paid,
            $paid > 0 => self::Partial,
            default => self::Unpaid,
        };
    }
}
