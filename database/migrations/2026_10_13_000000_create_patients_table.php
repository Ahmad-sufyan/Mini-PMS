<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // Assigned from the id straight after insert (see Patient::booted()).
            $table->string('mrn')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->string('phone', 30)->index();
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('blood_group', 3)->nullable();
            $table->text('allergies')->nullable();
            $table->text('notes')->nullable();
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone', 30);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
