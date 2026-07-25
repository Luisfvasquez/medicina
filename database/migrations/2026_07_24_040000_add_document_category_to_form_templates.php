<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add document_category column to form_templates.
     * Enables classifying templates by document type (clinical history,
     * consent form, certificate, etc.) for filtering and grouping.
     */
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->string('document_category', 50)
                  ->default('historia-clinica')
                  ->after('specialty')
                  ->comment('Document type: historia-clinica, consentimiento, certificado, formulario-evaluacion, etc.');
            $table->index('document_category');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropIndex(['document_category']);
            $table->dropColumn('document_category');
        });
    }
};
