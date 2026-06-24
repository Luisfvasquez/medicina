<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_documents', 'pending_upload')) {
                $table->boolean('pending_upload')->default(true);
            }
            if (!Schema::hasColumn('medical_documents', 'file_path')) {
                $table->string('file_path')->nullable();
            }
            if (!Schema::hasColumn('medical_documents', 'file_type')) {
                $table->string('file_type')->nullable();
            }
            if (!Schema::hasColumn('medical_documents', 'file_size')) {
                $table->integer('file_size')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_documents', function (Blueprint $table) {
            if (Schema::hasColumn('medical_documents', 'pending_upload')) {
                $table->dropColumn('pending_upload');
            }
            if (Schema::hasColumn('medical_documents', 'file_path')) {
                $table->dropColumn('file_path');
            }
            if (Schema::hasColumn('medical_documents', 'file_type')) {
                $table->dropColumn('file_type');
            }
            if (Schema::hasColumn('medical_documents', 'file_size')) {
                $table->dropColumn('file_size');
            }
        });
    }
};
