<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled: patients get their MRN from a "created"
     * event. Every demo account uses the password "password", and all of
     * the data is fake.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Clinic Admin',
            'email' => 'admin@demo.test',
        ]);

        User::factory()->receptionist()->create([
            'name' => 'Rita Reception',
            'email' => 'reception@demo.test',
        ]);

        User::factory()->doctor('General Practice')->create([
            'name' => 'Dr. Amina Khan',
            'email' => 'doctor@demo.test',
        ]);

        User::factory()->doctor('Pediatrics')->create([
            'name' => 'Dr. Daniel Reyes',
            'email' => 'doctor2@demo.test',
        ]);

        User::factory()->doctor('Dermatology')->create([
            'name' => 'Dr. Mei Chen',
            'email' => 'doctor3@demo.test',
        ]);

        // A locum who has since left keeps an account, but can't sign in.
        User::factory()->receptionist()->create([
            'name' => 'Leo Morgan',
            'email' => 'leo.morgan@demo.test',
            'is_active' => false,
        ]);

        // One transaction keeps SQLite from syncing to disk after every insert.
        DB::transaction(function (): void {
            Patient::factory()->count(400)->create();

            // Two duplicate registrations were deleted before anyone booked them.
            Patient::factory()->count(2)->create()->each(fn (Patient $patient) => $patient->delete());
        });

        $this->call([
            AppointmentSeeder::class,
            VisitSeeder::class,
            ServiceSeeder::class,
            InvoiceSeeder::class,
        ]);
    }
}
