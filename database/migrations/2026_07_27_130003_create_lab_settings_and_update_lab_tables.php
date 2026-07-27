<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->unique()->constrained('provider_profiles')->cascadeOnDelete();
            $table->integer('daily_max_slots')->default(20);
            $table->boolean('auto_quoting_enabled')->default(false);
            $table->string('default_currency', 10)->default('USD');
            $table->text('instructions_for_patient')->nullable();
            $table->timestamps();
        });

        Schema::table('lab_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_requests', 'is_external')) {
                $table->boolean('is_external')->default(false)->after('is_completed');
            }
            if (!Schema::hasColumn('lab_requests', 'external_patient_name')) {
                $table->string('external_patient_name')->nullable()->after('is_external');
            }
            if (!Schema::hasColumn('lab_requests', 'external_patient_document')) {
                $table->string('external_patient_document')->nullable()->after('external_patient_name');
            }
        });

        Schema::table('lab_results', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_results', 'attachments_json')) {
                $table->json('attachments_json')->nullable()->after('result_json');
            }
            if (!Schema::hasColumn('lab_results', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_settings');

        Schema::table('lab_requests', function (Blueprint $table) {
            $table->dropColumn(['is_external', 'external_patient_name', 'external_patient_document']);
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropColumn(['attachments_json', 'email_sent_at']);
        });
    }
};
