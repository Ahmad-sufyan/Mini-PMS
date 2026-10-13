<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitSeeder extends Seeder
{
    /**
     * Clinical pictures for the reasons appointments are booked for (see
     * AppointmentSeeder): the complaint, likely diagnoses and medicines,
     * each medicine as [name, dosage, frequency, duration, instructions].
     *
     * @var array<string, array{complaint: string, diagnoses: list<string>, medicines: list<array{string, string, string, string, string|null}>, fever?: bool}>
     */
    private const array CASES = [
        'Routine check-up' => [
            'complaint' => 'Routine health check',
            'diagnoses' => ['General examination, no abnormal findings', 'Vitamin D deficiency'],
            'medicines' => [['Vitamin D3', '1000 IU', 'Once daily', '1 month', 'With food']],
        ],
        'Fever and cough' => [
            'complaint' => 'Fever and cough for three days',
            'diagnoses' => ['Acute upper respiratory tract infection', 'Acute bronchitis'],
            'medicines' => [
                ['Paracetamol', '500 mg', 'Three times daily', '5 days', 'After meals'],
                ['Dextromethorphan syrup', '10 ml', 'Twice daily', '5 days', null],
            ],
            'fever' => true,
        ],
        'Blood pressure review' => [
            'complaint' => 'Blood pressure review',
            'diagnoses' => ['Essential hypertension, controlled', 'Essential hypertension, not yet controlled'],
            'medicines' => [['Amlodipine', '5 mg', 'Once daily', '1 month', 'In the morning']],
        ],
        'Diabetes follow-up' => [
            'complaint' => 'Diabetes review',
            'diagnoses' => ['Type 2 diabetes mellitus, controlled', 'Type 2 diabetes mellitus, raised HbA1c'],
            'medicines' => [['Metformin', '500 mg', 'Twice daily', '1 month', 'With meals']],
        ],
        'Prescription renewal' => [
            'complaint' => 'Repeat prescription',
            'diagnoses' => ['Hypercholesterolaemia, stable on treatment'],
            'medicines' => [['Atorvastatin', '20 mg', 'At bedtime', '1 month', null]],
        ],
        'Lab results review' => [
            'complaint' => 'Review of blood test results',
            'diagnoses' => ['Iron deficiency anaemia', 'Borderline high cholesterol'],
            'medicines' => [['Ferrous sulfate', '200 mg', 'Once daily', '1 month', 'Take on an empty stomach']],
        ],
        'Back pain' => [
            'complaint' => 'Lower back pain for a week',
            'diagnoses' => ['Mechanical low back pain', 'Lumbar muscle strain'],
            'medicines' => [
                ['Ibuprofen', '400 mg', 'Three times daily', '5 days', 'After meals'],
                ['Diclofenac gel', 'Apply thinly', 'Three times daily', '7 days', 'Rub into the painful area'],
            ],
        ],
        'Headaches' => [
            'complaint' => 'Recurring headaches',
            'diagnoses' => ['Tension-type headache', 'Migraine without aura'],
            'medicines' => [['Paracetamol', '1 g', 'As needed', '5 days', 'No more than 4 doses in 24 hours']],
        ],
        'Child wellness visit' => [
            'complaint' => 'Well-child check',
            'diagnoses' => ['Healthy child, normal growth and development'],
            'medicines' => [['Multivitamin syrup', '5 ml', 'Once daily', '1 month', null]],
        ],
        'Vaccination' => [
            'complaint' => 'Scheduled vaccination',
            'diagnoses' => ['Routine immunisation given'],
            'medicines' => [['Paracetamol syrup', '5 ml', 'As needed', '2 days', 'For fever after the vaccine']],
        ],
        'Ear infection' => [
            'complaint' => 'Ear pain and fever',
            'diagnoses' => ['Acute otitis media'],
            'medicines' => [
                ['Amoxicillin syrup', '250 mg', 'Three times daily', '7 days', 'Complete the full course'],
                ['Paracetamol syrup', '5 ml', 'Every 8 hours', '3 days', 'As needed for pain'],
            ],
            'fever' => true,
        ],
        'Fever' => [
            'complaint' => 'Fever since yesterday',
            'diagnoses' => ['Viral fever'],
            'medicines' => [
                ['Paracetamol syrup', '5 ml', 'Every 8 hours', '3 days', 'As needed for fever'],
                ['Oral rehydration salts', '1 sachet', 'Three times daily', '3 days', 'Dissolve in water'],
            ],
            'fever' => true,
        ],
        'Growth check' => [
            'complaint' => 'Growth review',
            'diagnoses' => ['Normal growth for age'],
            'medicines' => [['Vitamin D drops', '400 IU', 'Once daily', '1 month', null]],
        ],
        'Nappy rash' => [
            'complaint' => 'Red rash in the nappy area',
            'diagnoses' => ['Irritant nappy dermatitis'],
            'medicines' => [['Zinc oxide cream', 'Apply thinly', 'Four times daily', '7 days', 'At each nappy change']],
        ],
        'Acne treatment' => [
            'complaint' => 'Acne on the face and back',
            'diagnoses' => ['Acne vulgaris, moderate'],
            'medicines' => [
                ['Benzoyl peroxide 5% gel', 'Apply thinly', 'Once daily', '1 month', 'At night, avoid the eyes'],
                ['Doxycycline', '100 mg', 'Once daily', '1 month', 'Take with plenty of water'],
            ],
        ],
        'Skin rash' => [
            'complaint' => 'Itchy rash on the arms',
            'diagnoses' => ['Contact dermatitis', 'Urticaria'],
            'medicines' => [
                ['Cetirizine', '10 mg', 'Once daily', '7 days', 'May cause drowsiness'],
                ['Hydrocortisone 1% cream', 'Apply thinly', 'Twice daily', '7 days', null],
            ],
        ],
        'Mole check' => [
            'complaint' => 'Changing mole on the back',
            'diagnoses' => ['Benign melanocytic naevus'],
            'medicines' => [['Sunscreen SPF 50', 'Apply generously', 'Twice daily', 'Ongoing', 'Before going outdoors']],
        ],
        'Eczema follow-up' => [
            'complaint' => 'Eczema review',
            'diagnoses' => ['Atopic eczema, improving', 'Atopic eczema, flare'],
            'medicines' => [
                ['Emollient cream', 'Apply liberally', 'Three times daily', 'Ongoing', null],
                ['Hydrocortisone 1% cream', 'Apply thinly', 'Twice daily', '7 days', 'Only on red patches'],
            ],
        ],
        'Hair loss consultation' => [
            'complaint' => 'Thinning hair',
            'diagnoses' => ['Androgenetic alopecia', 'Telogen effluvium'],
            'medicines' => [['Minoxidil 5% solution', '1 ml', 'Twice daily', 'Ongoing', 'Apply to a dry scalp']],
        ],
        'Psoriasis review' => [
            'complaint' => 'Psoriasis review',
            'diagnoses' => ['Chronic plaque psoriasis'],
            'medicines' => [['Calcipotriol ointment', 'Apply thinly', 'Once daily', '1 month', null]],
        ],
    ];

    /**
     * The clinical picture for any other reason.
     *
     * @var array{complaint: string, diagnoses: list<string>, medicines: list<array{string, string, string, string, string|null}>}
     */
    private const array GENERAL = [
        'complaint' => 'General consultation',
        'diagnoses' => ['No acute illness found'],
        'medicines' => [['Multivitamin', '1 tablet', 'Once daily', '1 month', null]],
    ];

    /**
     * Short notes a doctor might leave.
     *
     * @var list<string>
     */
    private const array NOTES = [
        'Symptoms started a few days ago. No red flags on examination.',
        'Advised rest, fluids and to return if symptoms get worse.',
        'Discussed diet, exercise and sleep.',
        'Explained the diagnosis and treatment plan; patient agreed.',
        'Review again if no improvement within a week.',
    ];

    /**
     * Record a visit, with a prescription, for every completed appointment.
     */
    public function run(ClinicSchedule $clinic): void
    {
        DB::transaction(function () use ($clinic): void {
            // lazyById() pages by id, so the visits created along the way
            // don't shift later pages of the whereDoesntHave() results.
            Appointment::query()
                ->with('patient')
                ->where('status', AppointmentStatus::Completed)
                ->whereDoesntHave('visit')
                ->lazyById()
                ->each(fn (Appointment $appointment) => $this->recordVisit($clinic, $appointment));
        });
    }

    /**
     * Record the visit for one completed appointment.
     */
    private function recordVisit(ClinicSchedule $clinic, Appointment $appointment): void
    {
        $case = self::CASES[$appointment->reason] ?? self::GENERAL;
        $visitedAt = $appointment->starts_at->addMinutes(fake()->numberBetween(0, 10));
        $visitDay = $clinic->toLocal($visitedAt)->startOfDay();

        $visit = new Visit([
            ...$this->vitals($appointment->patient, $case['fever'] ?? false),
            'chief_complaint' => $case['complaint'],
            'diagnosis' => fake()->randomElement($case['diagnoses']),
            'clinical_notes' => implode(' ', fake()->randomElements(self::NOTES, fake()->numberBetween(1, 2))),
            'follow_up_date' => fake()->boolean(25)
                ? $visitDay->addDays(fake()->randomElement([7, 14, 30]))->toDateString()
                : null,
        ]);
        $visit->appointment_id = $appointment->id;
        $visit->patient_id = $appointment->patient_id;
        $visit->doctor_id = $appointment->doctor_id;
        $visit->visited_at = $visitedAt;
        $visit->save();

        $visit->prescriptionItems()->createMany(array_map(
            fn (array $medicine): array => array_combine(
                ['medicine', 'dosage', 'frequency', 'duration', 'instructions'],
                $medicine,
            ),
            $case['medicines'],
        ));
    }

    /**
     * Make up plausible vitals for the patient. Height and build are derived
     * from the MRN, so a patient's measurements stay consistent across visits.
     *
     * @return array<string, int|float|null>
     */
    private function vitals(Patient $patient, bool $fever): array
    {
        $seed = crc32($patient->mrn);
        $age = $patient->age;

        if ($age < 13) {
            $height = round(52 + $age * 6.2 + ($seed % 9), 1);
            $weight = round(3.5 + $age * 2.6 + ($seed % 5) * 0.4 + fake()->randomFloat(1, -0.3, 0.3), 1);
            $bp = $age >= 5 ? [fake()->numberBetween(95, 112), fake()->numberBetween(55, 72)] : [null, null];
            $pulse = fake()->numberBetween(80, 120);
        } else {
            $height = round(150 + ($seed % 40), 1);
            $bmi = 19 + ($seed % 120) / 10;
            $weight = round($bmi * ($height / 100) ** 2 + fake()->randomFloat(1, -1.5, 1.5), 1);
            $bp = [fake()->numberBetween(105, 150), fake()->numberBetween(65, 95)];
            $pulse = fake()->numberBetween(58, 100);
        }

        return [
            'bp_systolic' => $bp[0],
            'bp_diastolic' => $bp[1],
            'pulse' => $pulse,
            'temperature' => $fever ? fake()->randomFloat(1, 37.9, 39.3) : fake()->randomFloat(1, 36.3, 37.3),
            'weight_kg' => $weight,
            'height_cm' => $height,
        ];
    }
}
