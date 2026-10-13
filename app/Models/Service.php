<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A billable service on the clinic's price list.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $price
 */
#[Fillable(['code', 'name', 'price'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * The service billed for a doctor's consultation.
     */
    public const string CONSULTATION = 'consultation';

    /**
     * The service billed for a follow-up visit.
     */
    public const string FOLLOW_UP = 'follow_up';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }
}
