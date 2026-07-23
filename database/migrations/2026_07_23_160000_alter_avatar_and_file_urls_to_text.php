<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patient_accounts', function (Blueprint $table) {
            $table->text('avatar_url')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('logo_url')->nullable()->change();
            $table->text('signature_url')->nullable()->change();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->text('logo_url')->nullable()->change();
        });

        Schema::table('verification_documents', function (Blueprint $table) {
            $table->text('file_url')->nullable()->change();
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->text('file_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_accounts', function (Blueprint $table) {
            $table->string('avatar_url', 255)->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('logo_url', 255)->nullable()->change();
            $table->string('signature_url', 255)->nullable()->change();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->string('logo_url', 255)->nullable()->change();
        });

        Schema::table('verification_documents', function (Blueprint $table) {
            $table->string('file_url', 255)->nullable()->change();
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->string('file_url', 255)->nullable()->change();
        });
    }
};
