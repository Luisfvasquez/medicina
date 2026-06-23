<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert countries from UUID PK to BIGINT PK (hybrid pattern)
     */
    public function up(): void
    {
        // Step 1: Preserve original UUID id values in a temporary column
        DB::statement('ALTER TABLE countries ADD COLUMN id_uuid_original UUID');
        DB::statement('UPDATE countries SET id_uuid_original = id');

        // Step 2: Drop the old UUID primary key and cascade FKs
        DB::statement('ALTER TABLE states DROP CONSTRAINT IF EXISTS states_country_id_foreign');
        DB::statement('ALTER TABLE countries DROP CONSTRAINT IF EXISTS countries_pkey');

        // Step 3: Drop the old UUID id column
        DB::statement('ALTER TABLE countries DROP COLUMN id');

        // Step 4: Add new BIGINT auto-increment primary key
        DB::statement('ALTER TABLE countries ADD COLUMN id BIGSERIAL PRIMARY KEY');

        // Step 5: Update states.country_id to reference new BIGINT id using the preserved UUID mapping
        DB::statement('ALTER TABLE states ADD COLUMN country_id_new BIGINT');
        DB::statement('UPDATE states SET country_id_new = (SELECT c.id FROM countries c WHERE c.id_uuid_original = states.country_id LIMIT 1)');
        DB::statement('ALTER TABLE states ALTER COLUMN country_id_new SET NOT NULL');
        DB::statement('ALTER TABLE states DROP COLUMN country_id');
        DB::statement('ALTER TABLE states RENAME COLUMN country_id_new TO country_id');
        DB::statement('ALTER TABLE states ADD CONSTRAINT states_country_id_foreign FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE');

        // Step 6: Drop the temporary UUID column
        DB::statement('ALTER TABLE countries DROP COLUMN id_uuid_original');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Forward-only migration
    }
};
