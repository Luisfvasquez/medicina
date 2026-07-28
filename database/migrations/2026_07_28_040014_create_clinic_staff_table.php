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
        Schema::create('clinic_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_department_id')->nullable()->constrained()->nullOnDelete();
            
            $table->boolean('is_active')->default(true);
            $table->string('license_number')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Un usuario solo puede tener un registro de staff por sucursal
            $table->unique(['clinic_branch_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_staff');
    }
};
