<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert specialties from UUID PK to BIGINT PK (hybrid pattern)
     */
    public function up(): void
    {
        if (Schema::hasTable('specialties') && Schema::hasColumn('specialties', 'id')) {
            $type = strtolower(Schema::getColumnType('specialties', 'id'));
            if (str_contains($type, 'int') || $type === 'bigint') {
                return;
            }
        }

        // Tables referencing specialties.id
        $tablesWithSpecialtyFk = [
            'doctor_specialty' => ['constraint' => 'doctor_specialty_specialty_id_foreign', 'nullable' => false],
        ];

        // Step 1: Preserve original UUID id values
        DB::statement('ALTER TABLE specialties ADD COLUMN id_uuid_original UUID');
        DB::statement('UPDATE specialties SET id_uuid_original = id');

        // Step 2: Drop FK constraints
        foreach ($tablesWithSpecialtyFk as $table => $info) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$info['constraint']}");
        }

        // Step 3: Drop old primary key
        DB::statement('ALTER TABLE specialties DROP CONSTRAINT IF EXISTS specialties_pkey');
        DB::statement('ALTER TABLE specialties DROP COLUMN id');

        // Step 4: Add new BIGINT auto-increment primary key
        DB::statement('ALTER TABLE specialties ADD COLUMN id BIGSERIAL PRIMARY KEY');

        // Step 5: Update doctor_specialty.specialty_id to reference new BIGINT id
        DB::statement("ALTER TABLE doctor_specialty ADD COLUMN specialty_id_new BIGINT");
        DB::statement("UPDATE doctor_specialty SET specialty_id_new = (SELECT s.id FROM specialties s WHERE s.id_uuid_original = doctor_specialty.specialty_id LIMIT 1)");
        DB::statement("ALTER TABLE doctor_specialty ALTER COLUMN specialty_id_new SET NOT NULL");
        DB::statement("ALTER TABLE doctor_specialty DROP COLUMN specialty_id");
        DB::statement("ALTER TABLE doctor_specialty RENAME COLUMN specialty_id_new TO specialty_id");
        DB::statement("ALTER TABLE doctor_specialty ADD CONSTRAINT doctor_specialty_specialty_id_foreign FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE CASCADE");

        // Step 6: Drop temporary column
        DB::statement('ALTER TABLE specialties DROP COLUMN id_uuid_original');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Forward-only migration
    }
};
