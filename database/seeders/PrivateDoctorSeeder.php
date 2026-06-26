<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DoctorSchedule;
use App\Models\VerificationDocument;
use App\Enums\Weekday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrivateDoctorSeeder extends Seeder
{
    /**
     * Creates doctors WITHOUT clinic associations (private practice).
     */
    public function run(): void
    {
        $privateDoctors = [
            [
                'full_name' => 'Dr. Roberto Gómez Solano',
                'email' => 'dr.gomez@luca.com',
                'phone' => '+584141000001',
                'specialties' => ['Medicina General'],
                'city' => 'Caracas',
                'schedule' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'],
                'schedule_start' => '09:00',
                'schedule_end' => '18:00',
            ],
            [
                'full_name' => 'Dra. Carmen Lucía Torres',
                'email' => 'dra.torres@luca.com',
                'phone' => '+584141000002',
                'specialties' => ['Dermatología'],
                'city' => 'Caracas',
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
                'schedule_start' => '08:00',
                'schedule_end' => '16:00',
            ],
            [
                'full_name' => 'Dr. Alejandro José Mendoza',
                'email' => 'dr.mendoza.privado@luca.com',
                'phone' => '+584141000003',
                'specialties' => ['Cardiología'],
                'city' => 'Maracay',
                'schedule' => ['TUESDAY', 'THURSDAY', 'SATURDAY'],
                'schedule_start' => '07:00',
                'schedule_end' => '15:00',
            ],
        ];

        foreach ($privateDoctors as $doc) {
            // Check if already exists
            $existing = User::where('email', $doc['email'])->first();
            if ($existing) {
                $this->command->info("Doctor {$doc['email']} already exists, skipping.");
                continue;
            }

            // Find city
            $city = \App\Models\City::where('name', $doc['city'])->first();

            // Find specialty
            $specialty = \App\Models\Specialty::where('name', $doc['specialties'][0])->first();

            // Create doctor
            $doctor = User::create([
                'uuid' => Str::uuid(),
                'email' => $doc['email'],
                'password_hash' => Hash::make('password'),
                'full_name' => $doc['full_name'],
                'phone' => $doc['phone'],
                'role' => 'DOCTOR',
                'is_active' => true,
                'plan_type' => 'PRO',
                'city_id' => $city?->id,
                'logo_url' => null,
            ]);

            // Attach specialty
            if ($specialty) {
                $doctor->specialties()->attach($specialty->id);
            }

            // Create verification document (APPROVED)
            VerificationDocument::create([
                'uuid' => Str::uuid(),
                'user_id' => $doctor->id,
                'type' => 'MEDICAL_LICENSE',
                'file_url' => 'https://luca-health.s3.amazonaws.com/verification/' . Str::slug($doc['full_name']) . '_license.pdf',
                'status' => 'APPROVED',
                'comments' => 'Verificado automáticamente para práctica privada',
            ]);

            // Create schedules (WITHOUT clinic_branch_id - private practice)
            foreach ($doc['schedule'] as $day) {
                DoctorSchedule::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $doctor->id,
                    'clinic_branch_id' => null, // ← SIN clínica
                    'weekday' => Weekday::from($day),
                    'start_time' => $doc['schedule_start'],
                    'end_time' => $doc['schedule_end'],
                    'appointment_duration' => 30,
                    'max_per_slot' => 1,
                    'is_active' => true,
                ]);
            }

            $this->command->info("Created private doctor: {$doc['full_name']} (no clinic)");
        }
    }
}
