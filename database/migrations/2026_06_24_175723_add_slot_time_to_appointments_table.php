<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->time('slot_time')->nullable()->after('time');
            $table->index(['user_id', 'date', 'slot_time'], 'idx_appointment_doctor_date_slot');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointment_doctor_date_slot');
            $table->dropColumn('slot_time');
        });
    }
};
