<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BloodGroup: string
{
    use HasOptions;

    case APositive = 'A+';
    case ANegative = 'A-';
    case BPositive = 'B+';
    case BNegative = 'B-';
    case ABPositive = 'AB+';
    case ABNegative = 'AB-';
    case OPositive = 'O+';
    case ONegative = 'O-';

    /**
     * Get the human readable name of the blood group.
     */
    public function label(): string
    {
        return $this->value;
    }
}
