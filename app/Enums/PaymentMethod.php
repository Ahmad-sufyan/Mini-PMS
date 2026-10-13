<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentMethod: string
{
    use HasOptions;

    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    /**
     * Get the human readable name of the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::BankTransfer => 'Bank transfer',
        };
    }
}
