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
        Schema::table('medical_supply_orders', function (Blueprint $table) {
            $table->foreignId('provider_profile_id')->nullable()->after('medical_house_name')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_supply_orders', function (Blueprint $table) {
            $table->dropForeign(['provider_profile_id']);
            $table->dropColumn('provider_profile_id');
        });
    }
};
