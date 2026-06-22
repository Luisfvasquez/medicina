<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_items', function (Blueprint $table) {
            // 1. Rename dosage -> dose
            $table->renameColumn('dosage', 'dose');
            
            // 2. Drop string medication column
            $table->dropColumn('medication');
            
            // 3. Add medication_id FK
            $table->foreignId('medication_id')
                  ->nullable()
                  ->constrained('medications')
                  ->onDelete('set null');
            
            // 4. Add notes
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('template_items', function (Blueprint $table) {
            $table->dropForeign(['medication_id']);
            $table->dropColumn(['medication_id', 'notes']);
            
            $table->string('medication');
            $table->renameColumn('dose', 'dosage');
        });
    }
};
