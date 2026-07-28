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
        Schema::create('medical_supply_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surgical_operation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('prescribing_doctor_id')->constrained('clinic_staff')->cascadeOnDelete();
            
            $table->text('supplies_list'); // JSON or text listing the required supplies/implants
            $table->string('medical_house_name')->nullable(); // The external provider name, if chosen
            $table->string('status')->default('pending'); // pending, sent, fulfilled
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_supply_orders');
    }
};
