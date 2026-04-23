<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->runSqliteUp();
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'referred') NOT NULL DEFAULT 'scheduled'");
        } else {
            DB::statement("ALTER TABLE visits DROP CONSTRAINT IF EXISTS visits_status_check");
            DB::statement("ALTER TABLE visits ADD CONSTRAINT visits_status_check CHECK (status IN ('scheduled', 'in_progress', 'completed', 'referred'))");
            DB::statement("ALTER TABLE visits ALTER COLUMN status SET DEFAULT 'scheduled'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->runSqliteDown();
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('in_progress', 'completed') NOT NULL DEFAULT 'completed'");
        } else {
            DB::statement("ALTER TABLE visits DROP CONSTRAINT IF EXISTS visits_status_check");
            DB::statement("ALTER TABLE visits ADD CONSTRAINT visits_status_check CHECK (status IN ('in_progress', 'completed'))");
            DB::statement("ALTER TABLE visits ALTER COLUMN status SET DEFAULT 'completed'");
        }
    }

    private function runSqliteUp(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE visits_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                patient_id INTEGER NOT NULL,
                patient_name VARCHAR NOT NULL,
                doctor_id INTEGER NOT NULL,
                doctor_name VARCHAR NOT NULL,
                appointment_id INTEGER,
                visit_date DATE NOT NULL,
                visit_time TIME,
                chief_complaint TEXT,
                diagnosis TEXT,
                medical_notes TEXT,
                vital_signs TEXT,
                consultation_fee NUMERIC NOT NULL DEFAULT \'0\',
                status VARCHAR NOT NULL DEFAULT \'scheduled\' CHECK (status IN (\'scheduled\', \'in_progress\', \'completed\', \'referred\')),
                created_at DATETIME,
                updated_at DATETIME,
                workflow_status VARCHAR NOT NULL DEFAULT \'scheduled\',
                visit_number VARCHAR,
                FOREIGN KEY(patient_id) REFERENCES patients(id) ON DELETE CASCADE,
                FOREIGN KEY(doctor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
            )
        ');

        DB::statement('
            INSERT INTO visits_new (
                id, patient_id, patient_name, doctor_id, doctor_name, appointment_id,
                visit_date, visit_time, chief_complaint, diagnosis, medical_notes,
                vital_signs, consultation_fee, status, created_at, updated_at,
                workflow_status, visit_number
            )
            SELECT
                id, patient_id, patient_name, doctor_id, doctor_name, appointment_id,
                visit_date, visit_time, chief_complaint, diagnosis, medical_notes,
                vital_signs, consultation_fee, status, created_at, updated_at,
                workflow_status, visit_number
            FROM visits
        ');

        DB::statement('DROP TABLE visits');
        DB::statement('ALTER TABLE visits_new RENAME TO visits');

        DB::statement('CREATE INDEX visits_patient_id_index ON visits (patient_id)');
        DB::statement('CREATE INDEX visits_doctor_id_index ON visits (doctor_id)');
        DB::statement('CREATE INDEX visits_visit_date_index ON visits (visit_date)');
        DB::statement('CREATE INDEX visits_workflow_status_index ON visits (workflow_status)');
        DB::statement('CREATE UNIQUE INDEX visits_visit_number_unique ON visits (visit_number)');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function runSqliteDown(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE visits_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                patient_id INTEGER NOT NULL,
                patient_name VARCHAR NOT NULL,
                doctor_id INTEGER NOT NULL,
                doctor_name VARCHAR NOT NULL,
                appointment_id INTEGER,
                visit_date DATE NOT NULL,
                visit_time TIME,
                chief_complaint TEXT,
                diagnosis TEXT,
                medical_notes TEXT,
                vital_signs TEXT,
                consultation_fee NUMERIC NOT NULL DEFAULT \'0\',
                status VARCHAR NOT NULL DEFAULT \'completed\' CHECK (status IN (\'in_progress\', \'completed\')),
                created_at DATETIME,
                updated_at DATETIME,
                workflow_status VARCHAR NOT NULL DEFAULT \'scheduled\',
                visit_number VARCHAR,
                FOREIGN KEY(patient_id) REFERENCES patients(id) ON DELETE CASCADE,
                FOREIGN KEY(doctor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
            )
        ');

        DB::statement('
            INSERT INTO visits_new (
                id, patient_id, patient_name, doctor_id, doctor_name, appointment_id,
                visit_date, visit_time, chief_complaint, diagnosis, medical_notes,
                vital_signs, consultation_fee, status, created_at, updated_at,
                workflow_status, visit_number
            )
            SELECT
                id, patient_id, patient_name, doctor_id, doctor_name, appointment_id,
                visit_date, visit_time, chief_complaint, diagnosis, medical_notes,
                vital_signs, consultation_fee, status, created_at, updated_at,
                workflow_status, visit_number
            FROM visits
        ');

        DB::statement('DROP TABLE visits');
        DB::statement('ALTER TABLE visits_new RENAME TO visits');

        DB::statement('CREATE INDEX visits_patient_id_index ON visits (patient_id)');
        DB::statement('CREATE INDEX visits_doctor_id_index ON visits (doctor_id)');
        DB::statement('CREATE INDEX visits_visit_date_index ON visits (visit_date)');
        DB::statement('CREATE INDEX visits_workflow_status_index ON visits (workflow_status)');
        DB::statement('CREATE UNIQUE INDEX visits_visit_number_unique ON visits (visit_number)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
