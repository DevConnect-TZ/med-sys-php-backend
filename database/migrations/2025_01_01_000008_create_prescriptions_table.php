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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('patient_name');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->string('doctor_name');
            $table->foreignId('visit_id')->nullable()->constrained('visits')->onDelete('set null');
            $table->json('medications'); // Array of {name, dosage, frequency, duration, quantity, instructions}
            $table->enum('status', ['pending', 'dispensed', 'cancelled'])->default('pending');
            $table->date('prescription_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('patient_id');
            $table->index('status');
            $table->index('prescription_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
