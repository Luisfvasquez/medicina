<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('google_maps_url')->nullable()->after('rif');
            $table->text('observations')->nullable()->after('google_maps_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['google_maps_url', 'observations']);
        });
    }
};
