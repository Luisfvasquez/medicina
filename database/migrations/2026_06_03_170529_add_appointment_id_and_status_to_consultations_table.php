<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Agregar appointment_id primero (no tiene dependencias)
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('appointments')
                ->onDelete('set null');
            
            // Agregar status
            $table->string('status')
                ->default(\App\Enums\ConsultationStatus::PENDING->value)
                ->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropColumn('appointment_id');
            $table->dropColumn('status');
        });
    }
};
