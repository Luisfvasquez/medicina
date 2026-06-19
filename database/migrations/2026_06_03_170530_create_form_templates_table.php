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
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('Opcional: Si es una plantilla personalizada de un doctor');
            $table->string('specialty')->nullable()->comment('Ej: Cardiología. Si userId es null, es una plantilla global de la especialidad');
            $table->json('schema_json')->comment('Array con la estructura de los inputs: [{name, label, type}]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
