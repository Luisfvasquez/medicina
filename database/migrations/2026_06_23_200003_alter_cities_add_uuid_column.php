<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add uuid column to cities (hybrid pattern correction)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('cities', 'uuid')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            DB::table('cities')->update(['uuid' => DB::raw('gen_random_uuid()')]);

            Schema::table('cities', function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
