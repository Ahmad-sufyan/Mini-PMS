<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $doctor_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $duration_minutes
 * @property AppointmentStatus $status
 * @property string $reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Patient $patient
 * @property-read User $doctor
 * @property-read User|null $creator
 */
#[Fillable(['patient_id', 'doctor_id', 'starts_at', 'duration_minutes', 'reason', 'notes'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * The appointment lengths that can be booked, in minutes.
     *
     * @var list<int>
     */
    public const array DURATIONS = [15, 30, 45, 60, 90, 120];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
        'duration_minutes' => 30,
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            $appointment->ends_at = $appointment->starts_at->addMinutes($appointment->duration_minutes);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * Get the patient the appointment is for, even if their record was deleted.
     *
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /**
     * Get the doctor the appointment is with.
     *
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the member of staff who booked the appointment.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Limit appointments to the ones the user may see: everything for staff
     * who manage appointments, otherwise only the doctor's own.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->hasPermission(Permission::ManageAppointments)) {
            $query->where('doctor_id', $user->id);
        }
    }

    /**
     * Limit appointments to the ones that take up the doctor's time.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function occupying(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::occupying());
    }

    /**
     * Limit appointments to the ones that overlap the given period.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->where('starts_at', '<', CarbonImmutable::instance($end)->utc())
            ->where('ends_at', '>', CarbonImmutable::instance($start)->utc());
    }

    /**
     * Limit appointments to the ones starting within the given period.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function startingBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('starts_at', '>=', CarbonImmutable::instance($from)->utc())
            ->where('starts_at', '<=', CarbonImmutable::instance($to)->utc());
    }
}
