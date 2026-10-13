<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(private readonly ClinicSchedule $clinic) {}

    /**
     * Show the dashboard for the user's role: clinic figures and charts for
     * admins, the day's schedule for doctors, and the day's arrivals for
     * receptionists.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        return Inertia::render('dashboard', [
            'clinic' => ['name' => Config::string('clinic.name'), ...$this->clinic->toArray()],
            'today' => $this->clinic->today()->toDateString(),
            'dashboard' => match ($user->role) {
                Role::Admin => $this->admin(),
                Role::Doctor => $this->doctor($request, $user),
                Role::Receptionist => $this->reception($request, $user),
            },
        ]);
    }

    /**
     * The admin dashboard: headline figures and the last month's trends.
     *
     * @return array<string, mixed>
     */
    private function admin(): array
    {
        $today = $this->clinic->today();
        $monthStart = $today->startOfMonth();
        $chartStart = $today->subDays(29);

        $todaysAppointments = Appointment::query()
            ->startingBetween($today, $today->endOfDay())
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->pluck('status');

        $recentAppointments = Appointment::query()
            ->startingBetween($chartStart, $today->endOfDay())
            ->get(['starts_at', 'status']);

        $billable = Invoice::query()->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);

        return [
            'role' => 'admin',
            'stats' => [
                'patients' => Patient::query()->count(),
                'appointments_today' => $todaysAppointments->count(),
                'completed_today' => $todaysAppointments->filter(fn (AppointmentStatus $status) => $status === AppointmentStatus::Completed)->count(),
                'revenue_this_month' => $this->paymentsBetween($monthStart, $today->endOfDay()),
                'outstanding' => (int) (clone $billable)->sum('total')
                    - (int) Payment::query()->whereIn('invoice_id', (clone $billable)->select('id'))->sum('amount'),
                'outstanding_invoices' => (clone $billable)->count(),
            ],
            'appointmentsPerDay' => $this->appointmentsPerDay($recentAppointments, $chartStart),
            'revenuePerMonth' => $this->revenuePerMonth($monthStart->subMonths(5), 6),
            'appointmentsByStatus' => array_map(fn (AppointmentStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $recentAppointments->filter(fn (Appointment $appointment) => $appointment->status === $status)->count(),
            ], AppointmentStatus::cases()),
        ];
    }

    /**
     * The doctor dashboard: today's schedule, with the next patient to see.
     *
     * @return array<string, mixed>
     */
    private function doctor(Request $request, User $user): array
    {
        $appointments = $this->todaysAppointments()->where('doctor_id', $user->id)->get();
        $now = CarbonImmutable::now('UTC');

        // The next patient is whoever is waiting (checked in), otherwise
        // the next scheduled appointment that hasn't finished yet.
        $next = $appointments->first(fn (Appointment $appointment) => $appointment->status === AppointmentStatus::CheckedIn)
            ?? $appointments->first(fn (Appointment $appointment) => $appointment->status === AppointmentStatus::Scheduled
                && $appointment->ends_at->gt($now));

        return [
            'role' => 'doctor',
            'appointments' => $appointments->map(fn (Appointment $appointment) => AppointmentResource::make($appointment)->resolve($request)),
            'nextAppointmentId' => $next?->id,
            'stats' => $this->dayStats($appointments),
        ];
    }

    /**
     * The receptionist dashboard: today's appointments across all doctors.
     *
     * @return array<string, mixed>
     */
    private function reception(Request $request, User $user): array
    {
        $appointments = $this->todaysAppointments()->visibleTo($user)->get();

        return [
            'role' => 'reception',
            'appointments' => $appointments->map(fn (Appointment $appointment) => AppointmentResource::make($appointment)->resolve($request)),
            'stats' => $this->dayStats($appointments),
            'unpaidInvoices' => Invoice::query()->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])->count(),
        ];
    }

    /**
     * Get the query for today's appointments, in time order.
     *
     * @return Builder<Appointment>
     */
    private function todaysAppointments(): Builder
    {
        $today = $this->clinic->today();

        return Appointment::query()
            ->with(['patient:id,mrn,first_name,last_name', 'doctor:id,name,specialization'])
            ->startingBetween($today, $today->endOfDay())
            ->orderBy('starts_at')
            ->orderBy('id');
    }

    /**
     * Count the day's appointments by where they are in the visit.
     *
     * @param  EloquentCollection<int, Appointment>  $appointments
     * @return array{total: int, waiting: int, completed: int, to_come: int}
     */
    private function dayStats(EloquentCollection $appointments): array
    {
        $count = fn (AppointmentStatus $status): int => $appointments
            ->filter(fn (Appointment $appointment) => $appointment->status === $status)
            ->count();

        return [
            'total' => $appointments->count() - $count(AppointmentStatus::Cancelled),
            'waiting' => $count(AppointmentStatus::CheckedIn),
            'completed' => $count(AppointmentStatus::Completed),
            'to_come' => $count(AppointmentStatus::Scheduled),
        ];
    }

    /**
     * Count appointments (excluding cancellations) for each of the 30 days
     * from the given day, in clinic time.
     *
     * @param  EloquentCollection<int, Appointment>  $appointments
     * @return list<array{date: string, count: int}>
     */
    private function appointmentsPerDay(EloquentCollection $appointments, CarbonImmutable $from): array
    {
        $counts = $appointments
            ->filter(fn (Appointment $appointment) => $appointment->status !== AppointmentStatus::Cancelled)
            ->countBy(fn (Appointment $appointment) => $this->clinic->toLocal($appointment->starts_at)->toDateString());

        return array_map(function (int $offset) use ($from, $counts): array {
            $date = $from->addDays($offset)->toDateString();

            return ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
        }, range(0, 29));
    }

    /**
     * Total the payments received in each month, in clinic time, from the
     * month of the given day. Payments towards void invoices don't count.
     *
     * @return list<array{month: string, total: int}>
     */
    private function revenuePerMonth(CarbonImmutable $from, int $months): array
    {
        $totals = Payment::query()
            ->whereHas('invoice', fn ($query) => $query->where('status', '!=', InvoiceStatus::Void))
            ->where('paid_at', '>=', $from->startOfMonth()->utc())
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $this->clinic->toLocal($payment->paid_at)->format('Y-m'))
            ->map(fn ($payments) => (int) $payments->sum('amount'));

        return array_map(function (int $offset) use ($from, $totals): array {
            $month = $from->addMonths($offset)->format('Y-m');

            return ['month' => $month, 'total' => (int) ($totals[$month] ?? 0)];
        }, range(0, $months - 1));
    }

    /**
     * Total the payments received between two moments, excluding payments
     * towards void invoices.
     */
    private function paymentsBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) Payment::query()
            ->whereHas('invoice', fn ($query) => $query->where('status', '!=', InvoiceStatus::Void))
            ->whereBetween('paid_at', [$from->utc(), $to->utc()])
            ->sum('amount');
    }
}
