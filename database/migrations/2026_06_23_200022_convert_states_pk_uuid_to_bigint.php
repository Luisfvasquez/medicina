<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert states from UUID PK to BIGINT PK (hybrid pattern)
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('states') && Schema::hasColumn('states', 'id')) {
            $type = strtolower(Schema::getColumnType('states', 'id'));
            if (str_contains($type, 'int') || $type === 'bigint') {
                return;
            }
        }

        // Step 1: Preserve original UUID id values
        DB::statement('ALTER TABLE states ADD COLUMN id_uuid_original UUID');
        DB::statement('UPDATE states SET id_uuid_original = id');

        // Step 2: Drop FK from cities
        DB::statement('ALTER TABLE cities DROP CONSTRAINT IF EXISTS cities_state_id_foreign');

        // Step 3: Drop old primary key
        DB::statement('ALTER TABLE states DROP CONSTRAINT IF EXISTS states_pkey');
        DB::statement('ALTER TABLE states DROP COLUMN id');

        // Step 4: Add new BIGINT auto-increment primary key
        DB::statement('ALTER TABLE states ADD COLUMN id BIGSERIAL PRIMARY KEY');

        // Step 5: Update cities.state_id to reference new BIGINT id
        DB::statement('ALTER TABLE cities ADD COLUMN state_id_new BIGINT');
        DB::statement('UPDATE cities SET state_id_new = (SELECT s.id FROM states s WHERE s.id_uuid_original = cities.state_id LIMIT 1)');
        DB::statement('ALTER TABLE cities ALTER COLUMN state_id_new SET NOT NULL');
        DB::statement('ALTER TABLE cities DROP COLUMN state_id');
        DB::statement('ALTER TABLE cities RENAME COLUMN state_id_new TO state_id');
        DB::statement('ALTER TABLE cities ADD CONSTRAINT cities_state_id_foreign FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE');

        // Step 6: Drop temporary column
        DB::statement('ALTER TABLE states DROP COLUMN id_uuid_original');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Forward-only migration
    }
};
