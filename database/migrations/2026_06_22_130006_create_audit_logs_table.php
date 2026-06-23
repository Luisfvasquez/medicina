<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // user_id: ON DELETE SET NULL - logs persist if user is deleted
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            
            $table->string('action'); // VIEW, CREATE, UPDATE, DELETE, EXPORT, PRINT
            $table->string('resource'); // UUID of the entity
            $table->string('resource_type'); // e.g. Consultation, Prescription
            
            $table->jsonb('details')->nullable(); // { "old": {...}, "new": {...} }
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index(['resource_type', 'resource']);
        });
    }

    public function down(): void
    {
        // HIPAA: Audit logs should NEVER be deleted
        // But for migration rollback, we drop the table
        Schema::dropIfExists('audit_logs');
    }
};
