<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientSummaryResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicSchedule;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    /**
     * The number of appointments shown per page.
     */
    private const int PER_PAGE = 25;

    /**
     * Create a new controller instance.
     */
    public function __construct(private readonly ClinicSchedule $clinic) {}

    /**
     * Show the list of appointments, defaulting to today's.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $canManage = $user->hasPermission(Permission::ManageAppointments);
        $today = $this->clinic->today();

        $from = $this->clinic->parseDate($request->string('from')->value()) ?? $today;
        $to = $this->clinic->parseDate($request->string('to')->value()) ?? $from;

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $filters = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            // Doctors only ever see their own appointments.
            'doctor' => $canManage && $request->integer('doctor') > 0 ? $request->integer('doctor') : null,
            'status' => $request->enum('status', AppointmentStatus::class)?->value,
        ];

        $appointments = Appointment::query()
            ->visibleTo($user)
            ->with(['patient:id,mrn,first_name,last_name', 'doctor:id,name,specialization'])
            ->startingBetween($from->startOfDay(), $to->endOfDay())
            ->when($filters['doctor'], fn (Builder $query, int $doctor) => $query->where('doctor_id', $doctor))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Appointment $appointment) => AppointmentResource::make($appointment)->resolve($request));

        return Inertia::render('appointments/index', [
            'appointments' => $appointments,
            'filters' => $filters,
            'today' => $today->toDateString(),
            'doctors' => $canManage ? $this->doctors() : [],
            'statuses' => AppointmentStatus::options(),
        ]);
    }

    /**
     * Show a week of appointments as a grid.
     */
    public function week(Request $request, #[CurrentUser] User $user): Response
    {
        $canManage = $user->hasPermission(Permission::ManageAppointments);
        $today = $this->clinic->today();
        $start = ($this->clinic->parseDate($request->string('week')->value()) ?? $today)
            ->startOfWeek(CarbonInterface::MONDAY);
        $doctor = $canManage && $request->integer('doctor') > 0 ? $request->integer('doctor') : null;

        $appointments = Appointment::query()
            ->visibleTo($user)
            ->with(['patient:id,mrn,first_name,last_name', 'doctor:id,name,specialization'])
            ->startingBetween($start, $start->addDays(6)->endOfDay())
            ->when($doctor, fn (Builder $query, int $doctor) => $query->where('doctor_id', $doctor))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentResource::make($appointment)->resolve($request));

        $days = array_map(function (int $offset) use ($start, $today): array {
            $day = $start->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'is_working_day' => $this->clinic->isWorkingDay($day),
                'is_today' => $day->isSameDay($today),
            ];
        }, range(0, 6));

        return Inertia::render('appointments/week', [
            'week' => [
                'start' => $start->toDateString(),
                'previous' => $start->subWeek()->toDateString(),
                'next' => $start->addWeek()->toDateString(),
                'days' => $days,
            ],
            'appointments' => $appointments,
            'filters' => ['doctor' => $doctor],
            'today' => $today->toDateString(),
            'now' => $this->clinic->now()->format('H:i'),
            'doctors' => $canManage ? $this->doctors() : [],
            'clinic' => $this->clinic->toArray(),
        ]);
    }

    /**
     * Show the form for booking an appointment.
     */
    public function create(Request $request): Response
    {
        $patient = $request->integer('patient') > 0 ? Patient::find($request->integer('patient')) : null;

        return Inertia::render('appointments/create', [
            ...$this->formOptions(),
            'patient' => $patient ? PatientSummaryResource::make($patient)->resolve($request) : null,
            'defaultDate' => $this->clinic->nextWorkingDay($this->clinic->today())->toDateString(),
        ]);
    }

    /**
     * Book a new appointment.
     */
    public function store(StoreAppointmentRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $appointment = new Appointment($request->safe()->except(['date', 'time']));
        $appointment->starts_at = $request->startsAt();
        $appointment->created_by = $user->id;
        $appointment->save();

        $this->flashSaved($appointment, 'Appointment booked for :patient with :doctor on :date at :time.');

        return $this->redirectToDay($appointment);
    }

    /**
     * Show the form for rescheduling or editing an appointment.
     */
    public function edit(Request $request, Appointment $appointment): Response
    {
        $appointment->load(['patient', 'doctor']);

        return Inertia::render('appointments/edit', [
            ...$this->formOptions(),
            'appointment' => AppointmentResource::make($appointment)->resolve($request),
            'patient' => PatientSummaryResource::make($appointment->patient)->resolve($request),
        ]);
    }

    /**
     * Update an appointment.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->fill($request->safe()->except(['date', 'time']));
        $appointment->starts_at = $request->startsAt();
        $appointment->save();

        $this->flashSaved($appointment, 'Appointment for :patient with :doctor updated to :date at :time.');

        return $this->redirectToDay($appointment);
    }

    /**
     * Get the options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'doctors' => $this->doctors(activeOnly: true),
            'durations' => Appointment::DURATIONS,
            'clinic' => $this->clinic->toArray(),
        ];
    }

    /**
     * Get the doctors, for filters and the booking form.
     *
     * @return Collection<int, User>
     */
    private function doctors(bool $activeOnly = false): Collection
    {
        return User::query()
            ->where('role', Role::Doctor)
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'specialization']);
    }

    /**
     * Flash a success toast describing the saved appointment.
     */
    private function flashSaved(Appointment $appointment, string $message): void
    {
        $start = $this->clinic->toLocal($appointment->starts_at);

        Inertia::flash('toast', ['type' => 'success', 'message' => __($message, [
            'patient' => $appointment->patient->full_name,
            'doctor' => $appointment->doctor->name,
            'date' => $start->format('D j M'),
            'time' => $start->format('H:i'),
        ])]);
    }

    /**
     * Redirect to the list of appointments on the appointment's day.
     */
    private function redirectToDay(Appointment $appointment): RedirectResponse
    {
        $date = $this->clinic->toLocal($appointment->starts_at)->toDateString();

        return to_route('appointments.index', ['from' => $date, 'to' => $date]);
    }
}
