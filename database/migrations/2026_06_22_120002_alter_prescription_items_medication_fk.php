<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            // 1. Rename dosage -> dose (column rename)
            $table->renameColumn('dosage', 'dose');
            
            // 2. Drop string medication column
            $table->dropColumn('medication');
            
            // 3. Add medication_id FK
            $table->foreignId('medication_id')
                  ->nullable() // nullable because existing data has no FK
                  ->constrained('medications')
                  ->onDelete('set null');
            
            // 4. Add quantity
            $table->integer('quantity')->default(1);
            
            // 5. Add notes
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['medication_id']);
            $table->dropColumn(['medication_id', 'quantity', 'notes']);
            
            $table->string('medication'); // Restore original
            $table->renameColumn('dose', 'dosage'); // Restore original
        });
    }
};
