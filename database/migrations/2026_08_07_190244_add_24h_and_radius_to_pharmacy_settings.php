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
        Schema::table('pharmacy_settings', function (Blueprint $table) {
            $table->boolean('is_24_hours')->default(false)->after('auto_quoting_enabled');
            $table->decimal('delivery_radius_km', 5, 2)->default(5.0)->after('is_24_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_settings', function (Blueprint $table) {
            $table->dropColumn(['is_24_hours', 'delivery_radius_km']);
        });
    }
};
