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
        Schema::table('patient_accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('avatar_url');
            $table->string('status')->default(\App\Enums\AccountStatus::ACTIVE->value)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'status']);
        });
    }
};