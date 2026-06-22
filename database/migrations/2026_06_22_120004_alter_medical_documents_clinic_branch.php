<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_documents', function (Blueprint $table) {
            // Add clinic_branch_id FK (schema requirement: documents must carry branch info)
            $table->foreignId('clinic_branch_id')
                  ->nullable()
                  ->constrained('clinic_branches')
                  ->onDelete('set null')
                  ->after('patient_id');
            
            // content should be NOT NULL per schema
            $table->text('content')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('medical_documents', function (Blueprint $table) {
            $table->dropForeign(['clinic_branch_id']);
            $table->dropColumn('clinic_branch_id');
            
            // Revert content to nullable
            $table->text('content')->nullable()->change();
        });
    }
};
