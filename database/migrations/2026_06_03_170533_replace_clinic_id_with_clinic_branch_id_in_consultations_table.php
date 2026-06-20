<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Eliminar la FK y columna clinic_id vieja
            $table->dropForeign(['clinic_id']);
            $table->dropColumn('clinic_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('clinic_id')
                ->nullable()
                ->constrained('clinics')
                ->onDelete('set null');
        });
    }
};
