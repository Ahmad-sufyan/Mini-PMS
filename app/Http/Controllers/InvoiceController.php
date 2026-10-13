<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PatientSummaryResource;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Support\ClinicSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * The number of invoices shown per page.
     */
    private const int PER_PAGE = 20;

    /**
     * Create a new controller instance.
     */
    public function __construct(private readonly ClinicSchedule $clinic) {}

    /**
     * Show the invoices, with the billed, collected and outstanding totals
     * for the current filters.
     */
    public function index(Request $request): Response
    {
        $from = $this->clinic->parseDate($request->string('from')->value());
        $to = $this->clinic->parseDate($request->string('to')->value());

        if ($from && $to && $to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->enum('status', InvoiceStatus::class)?->value,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
        ];

        $query = Invoice::query()
            ->forPatientMatching($filters['search'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->issuedBetween($from?->startOfDay(), $to?->endOfDay());

        $totals = $this->totals($query);

        $invoices = $query
            ->with('patient:id,mrn,first_name,last_name')
            ->withSum('payments', 'amount')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => InvoiceResource::make($invoice)->resolve($request));

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'totals' => $totals,
            'filters' => $filters,
            'statuses' => InvoiceStatus::options(),
        ]);
    }

    /**
     * Show the form for creating an invoice, optionally for a visit
     * (?visit=, with the consultation prefilled) or a patient (?patient=).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $visit = $request->integer('visit') > 0
            ? Visit::with(['patient', 'doctor:id,name', 'appointment:id,reason'])->findOrFail($request->integer('visit'))
            : null;

        $invoiced = $visit?->invoices()->where('status', '!=', InvoiceStatus::Void)->first();

        if ($invoiced !== null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('That visit has already been invoiced.')]);

            return to_route('invoices.show', $invoiced);
        }

        $patient = $visit->patient
            ?? ($request->integer('patient') > 0 ? Patient::find($request->integer('patient')) : null);

        return Inertia::render('invoices/create', [
            ...$this->formOptions(),
            'patient' => $patient ? PatientSummaryResource::make($patient)->resolve($request) : null,
            'visit' => $visit ? [
                'id' => $visit->id,
                'date' => $this->clinic->toLocal($visit->visited_at)->toDateString(),
                'doctor' => $visit->doctor->name,
            ] : null,
            'items' => $visit ? [$this->visitLine($visit)] : [],
            'defaultDate' => $this->clinic->today()->toDateString(),
        ]);
    }

    /**
     * Create an invoice.
     */
    public function store(StoreInvoiceRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $visit = $request->visit();
        $items = $request->items();

        $invoice = DB::transaction(function () use ($request, $user, $visit, $items): Invoice {
            $issuedAt = $this->issuedAt($request->string('issued_on')->value());

            $invoice = new Invoice($request->safe()->only(['notes']));
            $invoice->patient_id = $visit->patient_id ?? $request->integer('patient_id');
            $invoice->visit_id = $visit?->id;
            $invoice->issued_at = $issuedAt;
            $invoice->discount = $request->discount();
            $invoice->created_by = $user->id;
            $invoice->applyTotals($items);
            $invoice->save();

            $invoice->items()->createMany($items);
            $invoice->refreshStatus();

            return $invoice;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice :number created.', ['number' => $invoice->invoice_number])]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Show an invoice with its lines and payment history.
     */
    public function show(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['patient', 'items', 'payments.receiver:id,name', 'visit.doctor:id,name']);

        return Inertia::render('invoices/show', [
            'invoice' => InvoiceResource::make($invoice)->resolve($request),
            'visit' => $invoice->visit ? [
                'id' => $invoice->visit->id,
                'date' => $this->clinic->toLocal($invoice->visit->visited_at)->toDateString(),
                'doctor' => $invoice->visit->doctor->name,
            ] : null,
            'paymentMethods' => PaymentMethod::options(),
            'today' => $this->clinic->today()->toDateString(),
        ]);
    }

    /**
     * Show the form for editing an unpaid invoice.
     */
    public function edit(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['patient', 'items']);

        return Inertia::render('invoices/edit', [
            ...$this->formOptions(),
            'invoice' => InvoiceResource::make($invoice)->resolve($request),
            'today' => $this->clinic->today()->toDateString(),
        ]);
    }

    /**
     * Update an unpaid invoice, replacing its lines.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $items = $request->items();

        DB::transaction(function () use ($request, $invoice, $items): void {
            $invoice->fill($request->safe()->only(['notes']));
            $invoice->issued_at = $this->issuedAt($request->string('issued_on')->value(), $invoice->issued_at);
            $invoice->discount = $request->discount();
            $invoice->applyTotals($items);
            $invoice->save();

            $invoice->items()->delete();
            $invoice->items()->createMany($items);
            $invoice->refreshStatus();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice :number updated.', ['number' => $invoice->invoice_number])]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Show the printable invoice.
     */
    public function print(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['patient', 'items', 'payments.receiver:id,name']);

        return Inertia::render('print/invoice', [
            'invoice' => InvoiceResource::make($invoice)->resolve($request),
            'clinic' => [
                'name' => Config::string('clinic.name'),
                'address' => Config::string('clinic.address'),
                'phone' => Config::string('clinic.phone'),
            ],
        ]);
    }

    /**
     * Void an unpaid invoice.
     */
    public function void(Invoice $invoice, #[CurrentUser] User $user): RedirectResponse
    {
        $invoice->status = InvoiceStatus::Void;
        $invoice->voided_at = CarbonImmutable::now('UTC');
        $invoice->voided_by = $user->id;
        $invoice->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice :number has been voided.', ['number' => $invoice->invoice_number])]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Get the billed, collected and outstanding totals for the filtered
     * invoices. Void invoices don't count.
     *
     * @param  Builder<Invoice>  $query
     * @return array{billed: int, collected: int, outstanding: int}
     */
    private function totals(Builder $query): array
    {
        $billable = (clone $query)->where('status', '!=', InvoiceStatus::Void);

        $billed = (int) (clone $billable)->sum('total');
        $collected = (int) Payment::query()->whereIn('invoice_id', (clone $billable)->select('id'))->sum('amount');

        return [
            'billed' => $billed,
            'collected' => $collected,
            'outstanding' => $billed - $collected,
        ];
    }

    /**
     * Get the options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'services' => Service::query()->orderBy('name')->get(['id', 'code', 'name', 'price']),
        ];
    }

    /**
     * Get the prefilled line for invoicing a visit: a follow-up if it was
     * booked as one, otherwise a consultation.
     *
     * @return array{service_id: int|null, description: string, quantity: int, unit_price: int}
     */
    private function visitLine(Visit $visit): array
    {
        $code = $visit->appointment?->reason === 'Follow-up visit' ? Service::FOLLOW_UP : Service::CONSULTATION;
        $service = Service::query()->where('code', $code)->first();

        return [
            'service_id' => $service?->id,
            'description' => __(':service with :doctor (:date)', [
                'service' => $service->name ?? 'Consultation',
                'doctor' => $visit->doctor->name,
                'date' => $this->clinic->toLocal($visit->visited_at)->format('j M Y'),
            ]),
            'quantity' => 1,
            'unit_price' => $service->price ?? 0,
        ];
    }

    /**
     * Get the moment an invoice dated on the given (clinic-local) day was
     * issued: now if it's today, otherwise that day, keeping the time of day
     * it already had when editing.
     */
    private function issuedAt(string $date, ?CarbonImmutable $current = null): CarbonImmutable
    {
        if ($current !== null && $this->clinic->toLocal($current)->toDateString() === $date) {
            return $current;
        }

        return $this->clinic->at($date, $this->clinic->now()->format('H:i'))->utc();
    }
}
