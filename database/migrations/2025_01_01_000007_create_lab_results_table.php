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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('patient_name');
            $table->string('test_name');
            $table->text('results')->nullable();
            $table->text('result_file_url')->nullable();
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->string('technician_name');
            $table->date('result_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('lab_order_id');
            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
