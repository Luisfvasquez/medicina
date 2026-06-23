<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add uuid column to countries (hybrid pattern correction)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('countries', 'uuid')) {
            // First add as nullable to avoid NOT NULL constraint violation on existing data
            Schema::table('countries', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            // Populate existing rows with UUIDs
            DB::table('countries')->update(['uuid' => DB::raw('gen_random_uuid()')]);

            // Now make it NOT NULL and unique
            Schema::table('countries', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
