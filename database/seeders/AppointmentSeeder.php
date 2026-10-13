<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    /**
     * Typical reasons for a visit, by the doctor's specialization.
     *
     * @var array<string, list<string>>
     */
    private const array REASONS = [
        'General Practice' => [
            'Routine check-up',
            'Fever and cough',
            'Blood pressure review',
            'Diabetes follow-up',
            'Prescription renewal',
            'Lab results review',
            'Back pain',
            'Headaches',
        ],
        'Pediatrics' => [
            'Child wellness visit',
            'Vaccination',
            'Ear infection',
            'Fever',
            'Growth check',
            'Nappy rash',
        ],
        'Dermatology' => [
            'Acne treatment',
            'Skin rash',
            'Mole check',
            'Eczema follow-up',
            'Hair loss consultation',
            'Psoriasis review',
        ],
    ];

    /**
     * Reasons used for doctors with any other specialization.
     *
     * @var list<string>
     */
    private const array GENERAL_REASONS = ['Consultation', 'Follow-up visit', 'Routine check-up'];

    /**
     * Occasional notes left when booking.
     *
     * @var list<string>
     */
    private const array NOTES = [
        'Patient asked for the first available slot.',
        'Bring previous test results.',
        'Requested a female doctor if possible.',
        'Booked by phone.',
        'Follow-up from last visit.',
    ];

    /**
     * Book every active doctor's working days from the start of the month
     * five months ago to 14 days ahead, with statuses that make sense for
     * the date: past appointments are mostly completed, current ones
     * checked in, future ones scheduled.
     */
    public function run(ClinicSchedule $clinic): void
    {
        $doctors = User::query()->where('role', Role::Doctor)->where('is_active', true)->get();
        $bookerIds = User::query()->whereIn('role', [Role::Admin, Role::Receptionist])->pluck('id');

        // When each patient registered, so nobody is booked before then.
        $registeredAt = Patient::query()->get(['id', 'created_at'])
            ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->created_at->getTimestamp()])
            ->all();

        if ($doctors->isEmpty() || $registeredAt === []) {
            return;
        }

        // The pediatrician sees children when there are any.
        $childIds = Patient::query()
            ->where('date_of_birth', '>', $clinic->today()->subYears(16)->toDateString())
            ->pluck('id');
        $children = array_intersect_key($registeredAt, $childIds->flip()->all());

        $now = $clinic->now();
        $lastDay = $clinic->today()->addDays(14);

        // One transaction keeps SQLite from syncing to disk after every insert.
        DB::transaction(function () use ($clinic, $now, $lastDay, $doctors, $registeredAt, $children, $bookerIds): void {
            for ($day = $clinic->today()->subMonths(5)->startOfMonth(); $day->lte($lastDay); $day = $day->addDay()) {
                if (! $clinic->isWorkingDay($day)) {
                    continue;
                }

                foreach ($doctors as $doctor) {
                    $patients = $doctor->specialization === 'Pediatrics' && $children !== []
                        ? $children
                        : $registeredAt;

                    $this->bookDay($clinic, $day, $doctor, $patients, $bookerIds, $now);
                }
            }
        });
    }

    /**
     * Fill one doctor's day with back-to-back appointments and a few gaps.
     *
     * @param  array<int, int>  $patients  registration timestamps, by patient ID
     * @param  Collection<int, int>  $bookerIds
     */
    private function bookDay(
        ClinicSchedule $clinic,
        CarbonImmutable $day,
        User $doctor,
        array $patients,
        Collection $bookerIds,
        CarbonImmutable $now,
    ): void {
        $closes = $day->setTimeFromTimeString($clinic->closesAt());
        $start = $day->setTimeFromTimeString($clinic->opensAt());
        $reasons = self::REASONS[$doctor->specialization] ?? self::GENERAL_REASONS;

        while (true) {
            $start = $start->addMinutes(fake()->randomElement([0, 0, 15, 30, 45, 60, 90]));
            $minutes = fake()->randomElement([15, 30, 30, 30, 45, 60]);
            $end = $start->addMinutes($minutes);

            if ($end->gt($closes)) {
                return;
            }

            // Booked one to fourteen days ahead, but never in the future,
            // for someone who had registered by then.
            $bookedAt = $start->subDays(fake()->numberBetween(1, 14))->min($now);
            $registered = array_keys(array_filter($patients, fn (int $registeredAt): bool => $registeredAt <= $bookedAt->getTimestamp()));

            if ($registered === []) {
                $start = $end;

                continue;
            }

            Appointment::factory()->create([
                'patient_id' => fake()->randomElement($registered),
                'doctor_id' => $doctor->id,
                'starts_at' => $start->utc(),
                'duration_minutes' => $minutes,
                'status' => $this->statusFor($start, $end, $now),
                'reason' => fake()->randomElement($reasons),
                'notes' => fake()->optional(0.15)->randomElement(self::NOTES),
                'created_by' => $bookerIds->isEmpty() ? null : $bookerIds->random(),
                'created_at' => $bookedAt->utc(),
                'updated_at' => $bookedAt->utc(),
            ]);

            $start = $end;
        }
    }

    /**
     * Pick a realistic status for an appointment, given the current time.
     */
    private function statusFor(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $now): AppointmentStatus
    {
        if ($end->lte($now)) {
            $roll = fake()->numberBetween(1, 100);

            return match (true) {
                $roll <= 82 => AppointmentStatus::Completed,
                $roll <= 92 => AppointmentStatus::Cancelled,
                default => AppointmentStatus::NoShow,
            };
        }

        if ($start->lte($now)) {
            return AppointmentStatus::CheckedIn;
        }

        return fake()->boolean(8) ? AppointmentStatus::Cancelled : AppointmentStatus::Scheduled;
    }
}
