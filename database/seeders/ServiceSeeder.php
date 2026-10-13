<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * The clinic's price list, in whole units of its currency.
     *
     * @var list<array{code: string, name: string, price: int}>
     */
    private const array SERVICES = [
        ['code' => Service::CONSULTATION, 'name' => 'Consultation', 'price' => 50],
        ['code' => Service::FOLLOW_UP, 'name' => 'Follow-up visit', 'price' => 30],
        ['code' => 'ecg', 'name' => 'ECG', 'price' => 40],
        ['code' => 'blood_test', 'name' => 'Blood test', 'price' => 25],
        ['code' => 'xray', 'name' => 'X-ray', 'price' => 60],
        ['code' => 'dressing', 'name' => 'Dressing', 'price' => 15],
    ];

    /**
     * Seed the price list.
     */
    public function run(): void
    {
        foreach (self::SERVICES as $service) {
            Service::query()->updateOrCreate(['code' => $service['code']], [
                'name' => $service['name'],
                'price' => $service['price'] * 10 ** Money::decimals(),
            ]);
        }
    }
}
