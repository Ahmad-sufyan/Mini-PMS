<?php

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    useClinicHours();

    // Wednesday 21 October 2026, at noon in the clinic.
    $this->travelTo('2026-10-21 12:00:00');

    $this->patient = Patient::factory()->create();
    $this->doctor = User::factory()->doctor('General Practice')->create();
    $this->colleague = User::factory()->doctor('Pediatrics')->create();
});

/**
 * Book an appointment for the shared test patient.
 */
function bookAppointment(User $doctor, string $date, string $time, AppointmentStatus $status): Appointment
{
    return Appointment::factory()
        ->for(test()->patient)
        ->for($doctor, 'doctor')
        ->at($date, $time)
        ->status($status)
        ->create();
}

/**
 * Create an invoice for the shared test patient, with payments as
 * [amount, paid at].
 *
 * @param  list<array{int, string}>  $payments
 */
function invoiceWithPayments(int $total, array $payments = [], InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
{
    $invoice = Invoice::factory()
        ->for(test()->patient)
        ->withItems([[1, $total]])
        ->create(['status' => $status, 'issued_at' => '2026-09-01 10:00:00']);

    foreach ($payments as [$amount, $paidAt]) {
        Payment::factory()->for($invoice)->create(['amount' => $amount, 'paid_at' => $paidAt]);
    }

    $invoice->refreshStatus();

    return $invoice;
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('admins see the clinic figures and the trends behind them', function () {
    Patient::factory()->create()->delete();

    bookAppointment($this->doctor, '2026-10-21', '09:00', AppointmentStatus::Completed);
    bookAppointment($this->doctor, '2026-10-21', '10:00', AppointmentStatus::Cancelled);
    bookAppointment($this->colleague, '2026-10-21', '14:00', AppointmentStatus::Scheduled);
    bookAppointment($this->doctor, '2026-10-20', '10:00', AppointmentStatus::Completed);
    bookAppointment($this->doctor, '2026-09-10', '10:00', AppointmentStatus::Completed);

    invoiceWithPayments(5000, [[2000, '2026-10-05 10:00:00']]);
    invoiceWithPayments(3000);
    invoiceWithPayments(4000, [[4000, '2026-09-15 10:00:00']]);
    invoiceWithPayments(1000, [[1000, '2026-10-06 10:00:00']], InvoiceStatus::Void);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('today', '2026-10-21')
            ->where('clinic.name', config('clinic.name'))
            ->where('dashboard.role', 'admin')
            ->where('dashboard.stats', [
                'patients' => 1,
                'appointments_today' => 2,
                'completed_today' => 1,
                'revenue_this_month' => 2000,
                'outstanding' => 6000,
                'outstanding_invoices' => 2,
            ])
            ->has('dashboard.appointmentsPerDay', 30)
            ->where('dashboard.appointmentsPerDay.0', ['date' => '2026-09-22', 'count' => 0])
            ->where('dashboard.appointmentsPerDay.28', ['date' => '2026-10-20', 'count' => 1])
            ->where('dashboard.appointmentsPerDay.29', ['date' => '2026-10-21', 'count' => 2])
            ->where('dashboard.revenuePerMonth', [
                ['month' => '2026-05', 'total' => 0],
                ['month' => '2026-06', 'total' => 0],
                ['month' => '2026-07', 'total' => 0],
                ['month' => '2026-08', 'total' => 0],
                ['month' => '2026-09', 'total' => 4000],
                ['month' => '2026-10', 'total' => 2000],
            ])
            ->where('dashboard.appointmentsByStatus', fn ($statuses) => collect($statuses)->pluck('count', 'status')->all() == [
                'scheduled' => 1,
                'checked_in' => 0,
                'completed' => 2,
                'cancelled' => 1,
                'no_show' => 0,
            ]),
        );
});

test('doctors see only their own schedule for today, with the waiting patient next', function () {
    $completed = bookAppointment($this->doctor, '2026-10-21', '09:00', AppointmentStatus::Completed);
    $waiting = bookAppointment($this->doctor, '2026-10-21', '11:30', AppointmentStatus::CheckedIn);
    $later = bookAppointment($this->doctor, '2026-10-21', '14:00', AppointmentStatus::Scheduled);
    bookAppointment($this->doctor, '2026-10-22', '10:00', AppointmentStatus::Scheduled);
    bookAppointment($this->colleague, '2026-10-21', '13:00', AppointmentStatus::Scheduled);

    $this->actingAs($this->doctor)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.role', 'doctor')
            ->where('dashboard.appointments', fn ($appointments) => collect($appointments)->pluck('id')->all() === [$completed->id, $waiting->id, $later->id])
            ->where('dashboard.nextAppointmentId', $waiting->id)
            ->where('dashboard.appointments.1.can_start_visit', true)
            ->where('dashboard.stats', ['total' => 3, 'waiting' => 1, 'completed' => 1, 'to_come' => 1])
            ->missing('dashboard.stats.revenue_this_month')
            ->missing('dashboard.unpaidInvoices'),
        );
});

test("with nobody waiting, the doctor's next patient is the next appointment still to come", function () {
    bookAppointment($this->doctor, '2026-10-21', '10:00', AppointmentStatus::Scheduled);
    $next = bookAppointment($this->doctor, '2026-10-21', '15:00', AppointmentStatus::Scheduled);

    $this->actingAs($this->doctor)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.nextAppointmentId', $next->id),
        );
});

test('a doctor with nothing left today has no next patient', function () {
    bookAppointment($this->doctor, '2026-10-21', '09:00', AppointmentStatus::Completed);

    $this->actingAs($this->doctor)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.nextAppointmentId', null)
            ->where('dashboard.stats.to_come', 0),
        );
});

test("receptionists see every doctor's appointments today, ready to check in", function () {
    $first = bookAppointment($this->doctor, '2026-10-21', '13:00', AppointmentStatus::Scheduled);
    $second = bookAppointment($this->colleague, '2026-10-21', '13:30', AppointmentStatus::Scheduled);
    $cancelled = bookAppointment($this->doctor, '2026-10-21', '15:00', AppointmentStatus::Cancelled);
    bookAppointment($this->doctor, '2026-10-20', '10:00', AppointmentStatus::Completed);

    invoiceWithPayments(5000, [[2000, '2026-10-05 10:00:00']]);
    invoiceWithPayments(3000);
    invoiceWithPayments(4000, [[4000, '2026-09-15 10:00:00']]);

    $this->actingAs(User::factory()->receptionist()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.role', 'reception')
            ->where('dashboard.appointments', fn ($appointments) => collect($appointments)->pluck('id')->all() === [$first->id, $second->id, $cancelled->id])
            ->where('dashboard.appointments.0.next_statuses', fn ($statuses) => collect($statuses)->pluck('value')->contains('checked_in'))
            ->where('dashboard.stats', ['total' => 2, 'waiting' => 0, 'completed' => 0, 'to_come' => 2])
            ->where('dashboard.unpaidInvoices', 2),
        );
});
