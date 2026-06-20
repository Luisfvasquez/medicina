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
        Schema::create('clinic_branch_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('clinic_branch_id')->constrained('clinic_branches')->onDelete('cascade');
            
            $table->string('role')->default(\App\Enums\ClinicRole::DOCTOR->value);
            $table->string('department')->nullable();
            $table->string('office_number')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->unique(['user_id', 'clinic_branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_branch_members');
    }
};
