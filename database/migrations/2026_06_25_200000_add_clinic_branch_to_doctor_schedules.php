<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->foreignId('clinic_branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('clinic_branches')
                ->onDelete('cascade');

            // Drop old unique constraint and create new one that allows multiple entries per branch
            $table->dropUnique('doctor_schedules_user_id_weekday_unique');
            $table->unique(['user_id', 'weekday', 'clinic_branch_id'], 'doctor_schedules_user_weekday_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropForeign(['clinic_branch_id']);
            $table->dropColumn('clinic_branch_id');
            $table->dropUnique('doctor_schedules_user_weekday_branch_unique');
            $table->unique(['user_id', 'weekday']);
        });
    }
};
