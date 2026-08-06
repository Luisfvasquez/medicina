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
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('city_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->integer('search_radius_km')->default(5)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'search_radius_km']);
        });
    }
};
