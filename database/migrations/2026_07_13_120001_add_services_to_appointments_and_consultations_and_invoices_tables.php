<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modificar appointments: hacer user_id nullable y agregar provider_service_id
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreignId('provider_service_id')->nullable()->constrained('provider_services')->onDelete('set null');
        });

        // 2. Modificar consultations: agregar servicios realizados
        Schema::table('consultations', function (Blueprint $table) {
            $table->json('services_performed')->nullable()->comment('Procedimientos y servicios realizados en la consulta');
        });

        // 3. Modificar invoices: agregar tipo para separar interno de cliente
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type')->default('CLIENT')->comment('CLIENT o INTERNAL (facturación administrativa/honorarios)');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('services_performed');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropColumn('provider_service_id');
            // Nota: revertir user_id a NOT NULL podría fallar si hay registros null en el rollback,
            // por lo que no forzamos la nulabilidad en reversa para evitar romper el rollback.
        });
    }
};
