<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VerificationDocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar todos los doctores activos
        $doctors = User::where('role', 'DOCTOR')
            ->where('is_active', true)
            ->get();

        $this->command->info("Found {$doctors->count()} active doctors");

        foreach ($doctors as $doctor) {
            // Verificar si ya tiene un verification document aprobado
            $hasApproved = VerificationDocument::where('user_id', $doctor->id)
                ->where('status', 'APPROVED')
                ->exists();

            if (!$hasApproved) {
                VerificationDocument::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $doctor->id,
                    'type' => 'MEDICAL_LICENSE',
                    'file_url' => 'https://luca-health.s3.amazonaws.com/verification/' . Str::slug($doctor->full_name) . '_license.pdf',
                    'status' => 'APPROVED',
                    'comments' => 'Documento verificado automáticamente por sistema',
                ]);

                $this->command->info("Created verification document for: {$doctor->email}");
            } else {
                $this->command->info("Doctor already has approved document: {$doctor->email}");
            }
        }
    }
}
