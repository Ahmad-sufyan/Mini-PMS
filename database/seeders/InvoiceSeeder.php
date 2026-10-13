<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Extra services sometimes billed for a visit, by the booking reason.
     *
     * @var array<string, list<string>>
     */
    private const array EXTRAS = [
        'Blood pressure review' => ['ecg'],
        'Diabetes follow-up' => ['blood_test'],
        'Lab results review' => ['blood_test'],
        'Routine check-up' => ['blood_test', 'ecg'],
        'Back pain' => ['xray'],
        'Skin rash' => ['dressing'],
        'Mole check' => ['dressing'],
    ];

    /**
     * Invoice most visits, oldest first so the numbers follow the dates,
     * and pay them in a realistic mix: older invoices are mostly paid,
     * recent ones are more often partly paid or unpaid.
     */
    public function run(): void
    {
        $services = Service::query()->get()->keyBy('code');
        $staffIds = User::query()->whereIn('role', [Role::Admin, Role::Receptionist])->pluck('id');
        $adminId = User::query()->where('role', Role::Admin)->value('id');

        if ($services->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($services, $staffIds, $adminId): void {
            Visit::query()
                ->with(['appointment:id,reason', 'doctor:id,name'])
                ->orderBy('visited_at')
                ->orderBy('id')
                ->lazy()
                ->each(function (Visit $visit) use ($services, $staffIds, $adminId): void {
                    if (fake()->boolean(88)) {
                        $this->invoiceVisit($visit, $services, $staffIds, $adminId);
                    }
                });
        });
    }

    /**
     * Invoice one visit and record its payments.
     *
     * @param  Collection<string, Service>  $services
     * @param  BaseCollection<int, int>  $staffIds
     */
    private function invoiceVisit(Visit $visit, Collection $services, BaseCollection $staffIds, ?int $adminId): void
    {
        $now = CarbonImmutable::now('UTC');
        $unit = 10 ** Money::decimals();
        $issuedAt = $visit->visited_at->addMinutes(fake()->numberBetween(10, 40))->min($now);

        $main = $services[$visit->appointment?->reason === 'Follow-up visit' ? Service::FOLLOW_UP : Service::CONSULTATION];
        $lines = [$this->line($main, "{$main->name} with {$visit->doctor->name}")];

        foreach (self::EXTRAS[$visit->appointment->reason ?? ''] ?? [] as $code) {
            if (isset($services[$code]) && fake()->boolean(50)) {
                $lines[] = $this->line($services[$code], $services[$code]->name);
            }
        }

        $invoice = new Invoice;
        $invoice->patient_id = $visit->patient_id;
        $invoice->visit_id = $visit->id;
        $invoice->issued_at = $issuedAt;
        $invoice->discount = fake()->boolean(10) ? fake()->randomElement([5, 10]) * $unit : 0;
        $invoice->created_by = $staffIds->isEmpty() ? null : $staffIds->random();
        $invoice->created_at = $issuedAt;
        $invoice->updated_at = $issuedAt;
        $invoice->applyTotals($lines);
        $invoice->save();
        $invoice->items()->createMany($lines);

        // A few unpaid invoices raised by mistake were voided.
        if (fake()->boolean(2)) {
            $invoice->status = InvoiceStatus::Void;
            $invoice->voided_at = $issuedAt->addHour()->min($now);
            $invoice->voided_by = $adminId;
            $invoice->save();

            return;
        }

        $daysAgo = $issuedAt->diffInDays($now);
        $roll = fake()->numberBetween(1, 100);
        [$paidInFull, $partlyPaid] = match (true) {
            $daysAgo > 30 => [85, 95],
            $daysAgo > 7 => [65, 85],
            default => [45, 70],
        };

        $amounts = match (true) {
            $roll <= $paidInFull => $this->splitInFull($invoice->total, $unit),
            $roll <= $partlyPaid => [$this->partOf($invoice->total, $unit)],
            default => [],
        };

        foreach ($amounts as $amount) {
            $payment = new Payment([
                'amount' => $amount,
                'method' => fake()->randomElement([
                    PaymentMethod::Cash, PaymentMethod::Cash, PaymentMethod::Card, PaymentMethod::Card, PaymentMethod::BankTransfer,
                ]),
                'paid_at' => $issuedAt->addDays(fake()->numberBetween(0, 10))->min($now),
            ]);
            $payment->received_by = $staffIds->isEmpty() ? null : $staffIds->random();
            $invoice->payments()->save($payment);
        }

        $invoice->refreshStatus();
    }

    /**
     * Make an invoice line for a service.
     *
     * @return array{service_id: int, description: string, quantity: int, unit_price: int}
     */
    private function line(Service $service, string $description): array
    {
        return [
            'service_id' => $service->id,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $service->price,
        ];
    }

    /**
     * Pay the total in one go, or now and then in two instalments.
     *
     * @return list<int>
     */
    private function splitInFull(int $total, int $unit): array
    {
        if ($total < 2 * $unit || ! fake()->boolean(20)) {
            return [$total];
        }

        $first = $this->partOf($total, $unit);

        return [$first, $total - $first];
    }

    /**
     * Get a part payment of roughly a third to two thirds of the total, in
     * whole currency units.
     */
    private function partOf(int $total, int $unit): int
    {
        $amount = intdiv(intdiv($total * fake()->numberBetween(30, 70), 100), $unit) * $unit;

        return min(max($amount, $unit), $total - 1);
    }
}
