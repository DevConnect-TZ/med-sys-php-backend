<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('visit_id')->constrained('appointments')->onDelete('set null');
            $table->index('appointment_id');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('visit_id')->constrained('appointments')->onDelete('set null');
            $table->index('appointment_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->after('visit_id')->constrained('appointments')->onDelete('set null');
            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn('appointment_id');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn('appointment_id');
        });

        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn('appointment_id');
        });
    }
};
