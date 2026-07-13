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
        // 1. Drop the unique index and foreign key from consultation_id
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
            $table->dropUnique(['consultation_id']);
        });

        // 2. Add patient_id and user_id as nullable first, and alter consultation_id to be nullable
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('consultation_id')->nullable()->change();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('cascade');
        });

        // 3. Populate user_id and patient_id for existing records from their related consultation
        \Illuminate\Support\Facades\DB::statement('
            UPDATE lab_requests 
            SET user_id = consultations.user_id, 
                patient_id = consultations.patient_id 
            FROM consultations 
            WHERE lab_requests.consultation_id = consultations.id
        ');

        // 4. Set user_id and patient_id to be NOT NULL
        Schema::table('lab_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            
            // Re-establish consultation_id foreign key
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_requests', function (Blueprint $table) {
            // Drop new foreign keys
            $table->dropForeign(['user_id']);
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['consultation_id']);
            
            // Drop columns
            $table->dropColumn(['user_id', 'patient_id']);
        });

        // Restore constraints
        Schema::table('lab_requests', function (Blueprint $table) {
            // Re-establish consultation_id as unique, non-nullable and foreign key
            $table->unsignedBigInteger('consultation_id')->nullable(false)->unique()->change();
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }
};
