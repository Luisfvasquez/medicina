<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasClinicId = Schema::hasColumn('prescriptions', 'clinic_id');
        $hasClinicBranchId = Schema::hasColumn('prescriptions', 'clinic_branch_id');

        if ($hasClinicId && !$hasClinicBranchId) {
            // Use raw SQL for reliability
            DB::statement('ALTER TABLE prescriptions DROP CONSTRAINT IF EXISTS prescriptions_clinic_id_foreign');
            DB::statement('ALTER TABLE prescriptions RENAME COLUMN clinic_id TO clinic_branch_id');
            DB::statement('ALTER TABLE prescriptions ADD CONSTRAINT prescriptions_clinic_branch_id_foreign FOREIGN KEY (clinic_branch_id) REFERENCES clinic_branches(id) ON DELETE SET NULL');
            DB::statement('ALTER TABLE prescriptions ALTER COLUMN clinic_branch_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        $hasClinicBranchId = Schema::hasColumn('prescriptions', 'clinic_branch_id');
        $hasClinicId = Schema::hasColumn('prescriptions', 'clinic_id');

        if ($hasClinicBranchId && !$hasClinicId) {
            DB::statement('ALTER TABLE prescriptions DROP CONSTRAINT IF EXISTS prescriptions_clinic_branch_id_foreign');
            DB::statement('ALTER TABLE prescriptions RENAME COLUMN clinic_branch_id TO clinic_id');
            DB::statement('ALTER TABLE prescriptions ADD CONSTRAINT prescriptions_clinic_id_foreign FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL');
            DB::statement('ALTER TABLE prescriptions ALTER COLUMN clinic_id DROP NOT NULL');
        }
    }
};
