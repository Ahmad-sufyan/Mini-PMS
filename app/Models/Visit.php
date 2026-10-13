<?php

namespace App\Models;

use App\Enums\Permission;
use Carbon\CarbonImmutable;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A consultation record: vitals, complaint, diagnosis, notes and the
 * medicines prescribed.
 *
 * @property int $id
 * @property int|null $appointment_id
 * @property int $patient_id
 * @property int $doctor_id
 * @property CarbonImmutable $visited_at
 * @property int|null $bp_systolic
 * @property int|null $bp_diastolic
 * @property int|null $pulse
 * @property float|null $temperature
 * @property float|null $weight_kg
 * @property float|null $height_cm
 * @property string $chief_complaint
 * @property string $diagnosis
 * @property string|null $clinical_notes
 * @property CarbonImmutable|null $follow_up_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Appointment|null $appointment
 * @property-read Patient $patient
 * @property-read User $doctor
 * @property-read Collection<int, PrescriptionItem> $prescriptionItems
 */
#[Fillable([
    'bp_systolic',
    'bp_diastolic',
    'pulse',
    'temperature',
    'weight_kg',
    'height_cm',
    'chief_complaint',
    'diagnosis',
    'clinical_notes',
    'follow_up_date',
])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'bp_systolic' => 'integer',
            'bp_diastolic' => 'integer',
            'pulse' => 'integer',
            'temperature' => 'float',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'follow_up_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Get the appointment the visit was for, if it wasn't a walk-in.
     *
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient who was seen, even if their record was deleted.
     *
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Get the doctor who saw the patient.
     *
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the medicines prescribed at the visit, in the order they were added.
     *
     * @return HasMany<PrescriptionItem, $this>
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('id');
    }

    /**
     * Get the invoices raised for the visit (at most one that isn't void).
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the body mass index from the weight and height, if both were taken.
     */
    public function bmi(): ?float
    {
        if (! $this->weight_kg || ! $this->height_cm) {
            return null;
        }

        return round($this->weight_kg / (($this->height_cm / 100) ** 2), 1);
    }

    /**
     * Get the adult BMI category for the visit's BMI.
     */
    public function bmiCategory(): ?string
    {
        $bmi = $this->bmi();

        return match (true) {
            $bmi === null => null,
            $bmi < 18.5 => 'Underweight',
            $bmi < 25 => 'Healthy weight',
            $bmi < 30 => 'Overweight',
            default => 'Obese',
        };
    }

    /**
     * Limit visits to the ones the user may see. Doctors only see the visits
     * they recorded; other staff see them all, but only staff with clinical
     * access receive the clinical details (see VisitResource).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->hasPermission(Permission::RecordVisits)) {
            $query->where('doctor_id', $user->id);
        }
    }
}
