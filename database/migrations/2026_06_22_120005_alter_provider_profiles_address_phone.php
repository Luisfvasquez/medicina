<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Add missing fields per schema
            $table->string('address')->nullable()->after('rif');
            $table->string('phone')->nullable()->after('address');
            $table->boolean('is_open')->default(false)->after('phone');
            
            // city_id as UUID (cities.id is UUID, not bigint)
            $table->uuid('city_id')->nullable()->after('address');
        });

        // Add FK separately (can't use constrained() with uuid)
        DB::statement('ALTER TABLE provider_profiles ADD CONSTRAINT provider_profiles_city_id_foreign FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL');

        // Make required fields NOT NULL per schema
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('commercial_name')->nullable(false)->change();
            $table->string('rif')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn(['city_id', 'address', 'phone', 'is_open']);
            
            $table->string('commercial_name')->nullable()->change();
            $table->string('rif')->nullable()->change();
        });
    }
};
