<?php

namespace App\Models;

use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $visit_id
 * @property string $medicine
 * @property string $dosage
 * @property string $frequency
 * @property string $duration
 * @property string|null $instructions
 * @property-read Visit $visit
 */
#[Fillable(['medicine', 'dosage', 'frequency', 'duration', 'instructions'])]
class PrescriptionItem extends Model
{
    /** @use HasFactory<PrescriptionItemFactory> */
    use HasFactory;

    /**
     * Common frequencies, suggested in the visit form.
     *
     * @var list<string>
     */
    public const array FREQUENCIES = [
        'Once daily',
        'Twice daily',
        'Three times daily',
        'Four times daily',
        'Every 8 hours',
        'At bedtime',
        'As needed',
    ];

    /**
     * Common durations, suggested in the visit form.
     *
     * @var list<string>
     */
    public const array DURATIONS = ['3 days', '5 days', '7 days', '10 days', '14 days', '1 month', 'Ongoing'];

    /**
     * Get the visit the medicine was prescribed at.
     *
     * @return BelongsTo<Visit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
