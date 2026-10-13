<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\Visits\StoreVisitRequest;
use App\Http\Requests\Visits\UpdateVisitRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\VisitResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(private readonly ClinicSchedule $clinic) {}

    /**
     * Show the visit form, for a checked-in appointment (?appointment=) or a
     * walk-in patient (?patient=).
     */
    public function create(Request $request): Response
    {
        $appointment = $request->integer('appointment') > 0
            ? Appointment::with(['patient', 'doctor'])->findOrFail($request->integer('appointment'))
            : null;

        Gate::authorize('create', [Visit::class, $appointment]);

        $patient = $appointment->patient ?? Patient::findOrFail($request->integer('patient'));

        return Inertia::render('visits/create', [
            ...$this->formOptions(),
            'patient' => $this->patientDetails($patient),
            'appointment' => $appointment ? AppointmentResource::make($appointment)->resolve($request) : null,
            'today' => $this->clinic->today()->toDateString(),
        ]);
    }

    /**
     * Save a visit. Saving the visit for an appointment completes it.
     */
    public function store(StoreVisitRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $appointment = $request->appointment();

        $visit = DB::transaction(function () use ($request, $user, $appointment): Visit {
            $visit = new Visit($request->safe()->except(['appointment_id', 'patient_id', 'prescriptions']));
            $visit->appointment_id = $appointment?->id;
            $visit->patient_id = $appointment->patient_id ?? $request->integer('patient_id');
            $visit->doctor_id = $user->id;
            $visit->visited_at = CarbonImmutable::now('UTC');
            $visit->save();

            $visit->prescriptionItems()->createMany($request->prescriptions());

            if ($appointment !== null) {
                $appointment->status = AppointmentStatus::Completed;
                $appointment->save();
            }

            return $visit;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $appointment
            ? __('Visit saved and the appointment is completed.')
            : __('Visit saved.'),
        ]);

        return to_route('patients.show', ['patient' => $visit->patient_id, 'tab' => 'visits']);
    }

    /**
     * Show the form for editing a visit.
     */
    public function edit(Request $request, Visit $visit): Response
    {
        $visit->load(['patient', 'doctor', 'prescriptionItems']);

        return Inertia::render('visits/edit', [
            ...$this->formOptions(),
            'visit' => VisitResource::make($visit)->resolve($request),
            'patient' => $this->patientDetails($visit->patient),
        ]);
    }

    /**
     * Update a visit, replacing its prescription.
     */
    public function update(UpdateVisitRequest $request, Visit $visit): RedirectResponse
    {
        DB::transaction(function () use ($request, $visit): void {
            $visit->update($request->safe()->except(['prescriptions']));

            $visit->prescriptionItems()->delete();
            $visit->prescriptionItems()->createMany($request->prescriptions());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Visit updated.')]);

        return to_route('patients.show', ['patient' => $visit->patient_id, 'tab' => 'visits']);
    }

    /**
     * Show the printable prescription for a visit.
     */
    public function prescription(Request $request, Visit $visit): Response
    {
        $visit->load(['patient', 'doctor', 'prescriptionItems']);

        return Inertia::render('print/prescription', [
            'visit' => VisitResource::make($visit)->resolve($request),
            'patient' => $this->patientDetails($visit->patient),
            'clinic' => [
                'name' => Config::string('clinic.name'),
                'address' => Config::string('clinic.address'),
                'phone' => Config::string('clinic.phone'),
            ],
        ]);
    }

    /**
     * Get the options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'frequencies' => PrescriptionItem::FREQUENCIES,
            'durations' => PrescriptionItem::DURATIONS,
        ];
    }

    /**
     * Get the patient details shown on the visit form and prescription.
     *
     * @return array<string, mixed>
     */
    private function patientDetails(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'mrn' => $patient->mrn,
            'full_name' => $patient->full_name,
            'age' => $patient->age,
            'gender' => $patient->gender->label(),
            'date_of_birth' => $patient->date_of_birth->toDateString(),
            'allergies' => $patient->allergies,
        ];
    }
}
