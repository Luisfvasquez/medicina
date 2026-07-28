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
        Schema::create('surgical_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgical_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_staff_id')->constrained('clinic_staff')->cascadeOnDelete();
            
            $table->string('role'); // e.g. lead_surgeon, anesthesiologist, scrub_nurse
            $table->boolean('is_primary')->default(false);
            
            $table->timestamps();
            
            // Ensures a staff member isn't added twice to the same operation in the same role
            $table->unique(['surgical_operation_id', 'clinic_staff_id', 'role'], 'surgical_team_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgical_teams');
    }
};
