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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('patient_name');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->string('doctor_name');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->date('visit_date');
            $table->text('chief_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('medical_notes')->nullable();
            $table->json('vital_signs')->nullable(); // {bp, temp, pulse, weight}
            $table->decimal('consultation_fee', 10, 2)->default(0.00);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'referred'])->default('scheduled');
            $table->string('visit_number')->nullable()->unique();
            $table->time('visit_time')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('visit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
