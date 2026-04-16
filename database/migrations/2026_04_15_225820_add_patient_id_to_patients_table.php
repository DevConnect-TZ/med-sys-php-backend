<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_id', 20)->nullable()->after('patient_number');
            $table->index('patient_id');
        });

        // Backfill patient_id for existing records
        $patients = DB::table('patients')->orderBy('id')->get();
        $yearCounters = [];

        foreach ($patients as $patient) {
            $year = \Carbon\Carbon::parse($patient->created_at)->format('Y');
            if (!isset($yearCounters[$year])) {
                $yearCounters[$year] = 0;
            }
            $yearCounters[$year]++;
            $patientId = $year . '-' . str_pad($yearCounters[$year], 4, '0', STR_PAD_LEFT);
            
            DB::table('patients')->where('id', $patient->id)->update(['patient_id' => $patientId]);
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_id', 20)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
