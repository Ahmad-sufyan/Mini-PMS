<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Every demo account uses the password "password".
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
    }
}
