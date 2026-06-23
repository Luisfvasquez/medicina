<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('patient_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('patient_accounts')
                ->onDelete('cascade');

            $table->index('patient_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['patient_account_id']);
            $table->dropIndex(['patient_account_id']);
            $table->dropColumn('patient_account_id');
        });
    }
};
