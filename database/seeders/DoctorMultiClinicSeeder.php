<?php

namespace Database\Seeders;

use App\Models\ClinicBranch;
use App\Models\ClinicBranchMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DoctorMultiClinicSeeder extends Seeder
{
    public function run(): void
    {
        // Dr. Roberto Carlos Mendoza - ya existe en Clínica Caracas (branch 1)
        // Lo agregamos también a la Clínica de Maracay
        $doctor = User::where('email', 'doctor.mendoza@luca.com')->first();

        if (!$doctor) {
            $this->command->warn('Dr. Mendoza no encontrado. Ejecuta CatalogSeeder primero.');
            return;
        }

        // Buscar la clínica de Maracay
        $maracayBranch = ClinicBranch::whereHas('clinic', function ($q) {
            $q->where('name', 'Centro Médico de Maracay');
        })->first();

        if (!$maracayBranch) {
            $this->command->warn('Sucursal de Maracay no encontrada.');
            return;
        }

        // Verificar que no esté ya asignado
        $exists = ClinicBranchMember::where('user_id', $doctor->id)
            ->where('clinic_branch_id', $maracayBranch->id)
            ->exists();

        if ($exists) {
            $this->command->info('Dr. Mendoza ya está asignado a Maracay.');
            return;
        }

        // Asignar a Maracay
        ClinicBranchMember::create([
            'uuid' => Str::uuid(),
            'user_id' => $doctor->id,
            'clinic_branch_id' => $maracayBranch->id,
            'role' => 'DOCTOR',
            'department' => 'Cardiología',
            'office_number' => 'Consultorio 201',
            'is_active' => true,
        ]);

        $this->command->info("Dr. Roberto Carlos Mendoza asignado a Maracay como segundo clinic.");
    }
}
