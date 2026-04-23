<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'visit_number')) {
                $table->string('visit_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('visits', 'visit_time')) {
                $table->time('visit_time')->nullable()->after('visit_date');
            }
        });

        // Fix enum to include 'scheduled' if using MySQL/strict DB
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'referred') DEFAULT 'scheduled'");
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN; assume fresh or handled manually
        } else {
            // PostgreSQL
            DB::statement("ALTER TABLE visits DROP CONSTRAINT IF EXISTS visits_status_check");
            DB::statement("ALTER TABLE visits ADD CONSTRAINT visits_status_check CHECK (status IN ('scheduled', 'in_progress', 'completed'))");
        }
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (Schema::hasColumn('visits', 'visit_number')) {
                $table->dropColumn('visit_number');
            }
            if (Schema::hasColumn('visits', 'visit_time')) {
                $table->dropColumn('visit_time');
            }
        });
    }
};
