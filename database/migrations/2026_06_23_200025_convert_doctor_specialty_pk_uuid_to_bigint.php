<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert doctor_specialty from UUID PK to BIGINT PK (hybrid pattern)
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('doctor_specialty') && Schema::hasColumn('doctor_specialty', 'id')) {
            $type = strtolower(Schema::getColumnType('doctor_specialty', 'id'));
            if (str_contains($type, 'int') || $type === 'bigint') {
                return;
            }
        }

        // Step 1: Preserve original UUID id values
        DB::statement('ALTER TABLE doctor_specialty ADD COLUMN id_uuid_original UUID');
        DB::statement('UPDATE doctor_specialty SET id_uuid_original = id');

        // Step 2: Drop old primary key
        DB::statement('ALTER TABLE doctor_specialty DROP CONSTRAINT IF EXISTS doctor_specialty_pkey');
        DB::statement('ALTER TABLE doctor_specialty DROP COLUMN id');

        // Step 3: Add new BIGINT auto-increment primary key
        DB::statement('ALTER TABLE doctor_specialty ADD COLUMN id BIGSERIAL PRIMARY KEY');

        // Step 4: Drop temporary column
        DB::statement('ALTER TABLE doctor_specialty DROP COLUMN id_uuid_original');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Forward-only migration
    }
};
