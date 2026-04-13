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
        Schema::create('pharmacy_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('dosage', 100)->nullable();
            $table->string('form', 100)->nullable(); // Tablet, Syrup, Injection, etc.
            $table->string('manufacturer')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('reorder_level')->default(100);
            $table->decimal('unit_price', 10, 2);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('medication_name');
            $table->index('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory');
    }
};
