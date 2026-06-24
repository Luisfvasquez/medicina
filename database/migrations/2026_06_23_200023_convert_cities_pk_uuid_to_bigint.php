<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert cities from UUID PK to BIGINT PK (hybrid pattern)
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'id')) {
            $type = strtolower(Schema::getColumnType('cities', 'id'));
            if (str_contains($type, 'int') || $type === 'bigint') {
                return;
            }
        }

        // Tables that reference cities.id (discovered via error message)
        $tablesWithCityFk = [
            'users' => ['constraint' => 'users_city_id_fkey', 'nullable' => true],
            'patient_accounts' => ['constraint' => 'patient_accounts_city_id_fkey', 'nullable' => true],
            'patients' => ['constraint' => 'patients_city_id_fkey', 'nullable' => true],
            'clinic_branches' => ['constraint' => 'clinic_branches_city_id_fkey', 'nullable' => false],
            'provider_branches' => ['constraint' => 'provider_branches_city_id_fkey', 'nullable' => false],
            'provider_profiles' => ['constraint' => 'provider_profiles_city_id_fkey', 'nullable' => true],
            'quote_requests' => ['constraint' => 'quote_requests_city_id_fkey', 'nullable' => true],
        ];

        // Step 1: Preserve original UUID id values
        DB::statement('ALTER TABLE cities ADD COLUMN id_uuid_original UUID');
        DB::statement('UPDATE cities SET id_uuid_original = id');

        // Step 2: Drop all FK constraints using CASCADE first
        foreach ($tablesWithCityFk as $table => $info) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$info['constraint']}");
        }

        // Step 3: Also need to drop provider_profiles_city_id_fkey if it exists with different naming
        DB::statement("ALTER TABLE provider_profiles DROP CONSTRAINT IF EXISTS provider_profiles_city_id_fkey");

        // Step 4: Drop old primary key (using CASCADE to ensure)
        DB::statement('ALTER TABLE cities DROP CONSTRAINT IF EXISTS cities_pkey CASCADE');

        // Step 5: Drop the old id column
        DB::statement('ALTER TABLE cities DROP COLUMN id');

        // Step 6: Add new BIGINT auto-increment primary key
        DB::statement('ALTER TABLE cities ADD COLUMN id BIGSERIAL PRIMARY KEY');

        // Step 7: Update all dependent tables' city_id to reference new BIGINT id
        foreach ($tablesWithCityFk as $table => $info) {
            $nullable = $info['nullable'] ? '' : 'SET NOT NULL';
            DB::statement("ALTER TABLE {$table} ADD COLUMN city_id_new BIGINT");
            DB::statement("UPDATE {$table} SET city_id_new = (SELECT c.id FROM cities c WHERE c.id_uuid_original = {$table}.city_id LIMIT 1)");
            if ($info['nullable']) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN city_id_new DROP NOT NULL");
            } else {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN city_id_new SET NOT NULL");
            }
            DB::statement("ALTER TABLE {$table} DROP COLUMN city_id");
            DB::statement("ALTER TABLE {$table} RENAME COLUMN city_id_new TO city_id");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$info['constraint']} FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE RESTRICT");
        }

        // Step 8: Drop temporary column
        DB::statement('ALTER TABLE cities DROP COLUMN id_uuid_original');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // Forward-only migration
    }
};
