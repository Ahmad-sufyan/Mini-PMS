<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use Carbon\CarbonImmutable;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $mrn
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $date_of_birth
 * @property Gender $gender
 * @property string $phone
 * @property string|null $email
 * @property string $address
 * @property BloodGroup|null $blood_group
 * @property string|null $allergies
 * @property string|null $notes
 * @property string $emergency_contact_name
 * @property string $emergency_contact_phone
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read string $full_name
 * @property-read int $age
 */
#[Fillable([
    'first_name',
    'last_name',
    'date_of_birth',
    'gender',
    'phone',
    'email',
    'address',
    'blood_group',
    'allergies',
    'notes',
    'emergency_contact_name',
    'emergency_contact_phone',
])]
#[Appends(['full_name', 'age'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The columns the patient list can be sorted by.
     *
     * @var list<string>
     */
    public const array SORTABLE = ['name', 'mrn', 'age', 'registered'];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        // Deriving the MRN from the auto-incrementing id keeps it unique
        // without locking, and ids (and so MRNs) are never reused.
        static::created(function (Patient $patient): void {
            $patient->forceFill(['mrn' => static::formatMrn($patient->id)])->saveQuietly();
        });
    }

    /**
     * Format the medical record number for the given patient id.
     */
    public static function formatMrn(int $id): string
    {
        return sprintf('MRN-%06d', $id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'gender' => Gender::class,
            'blood_group' => BloodGroup::class,
        ];
    }

    /**
     * Get the patient's full name.
     *
     * @return Attribute<non-falsy-string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the patient's age in whole years.
     *
     * @return Attribute<int, never>
     */
    protected function age(): Attribute
    {
        return Attribute::get(fn (): int => $this->date_of_birth->age);
    }

    /**
     * Filter patients by name, MRN or phone. Every word must match one of them,
     * so "jane smith" finds Jane Smith.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $query->where(fn (Builder $query) => $query
                ->whereLike('first_name', "%{$word}%")
                ->orWhereLike('last_name', "%{$word}%")
                ->orWhereLike('mrn', "%{$word}%")
                ->orWhereLike('phone', "%{$word}%"));
        }
    }

    /**
     * Sort patients by one of the sortable columns.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sortBy(Builder $query, string $sort, string $direction): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        match ($sort) {
            // MRNs follow the id, and a lower date of birth means an older patient.
            'mrn' => $query->orderBy('id', $direction),
            'age' => $query->orderBy('date_of_birth', $direction === 'asc' ? 'desc' : 'asc'),
            'registered' => $query->orderBy('created_at', $direction),
            default => $query->orderBy('last_name', $direction)->orderBy('first_name', $direction),
        };

        $query->orderBy('id', $direction);
    }
}
