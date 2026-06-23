<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Specialty;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Models\PatientAccount;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $caracas = City::where('name', 'Caracas')->first();
        $cityId = $caracas ? $caracas->id : null;

        // 1. Admin User
        User::create([
            'uuid' => Str::uuid(),
            'email' => 'admin@luca.com',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Administrador Luca',
            'phone' => '+584120000001',
            'role' => 'ADMIN',
            'is_active' => true,
            'plan_type' => 'ENTERPRISE',
            'city_id' => $cityId,
        ]);

        // 2. Doctor User
        $doctor = User::create([
            'uuid' => Str::uuid(),
            'email' => 'doctor@luca.com',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Dr. Carlos Mendoza',
            'phone' => '+584120000002',
            'role' => 'DOCTOR',
            'is_active' => true,
            'plan_type' => 'PRO',
            'city_id' => $cityId,
        ]);

        // Link doctor to specialties
        $medGeneral = Specialty::where('name', 'Medicina General')->first();
        $cardiologia = Specialty::where('name', 'Cardiología')->first();

        if ($medGeneral) {
            $doctor->specialties()->attach($medGeneral->id);
        }
        if ($cardiologia) {
            $doctor->specialties()->attach($cardiologia->id);
        }

        // 3. Provider User (Pharmacy Owner)
        $providerUser = User::create([
            'uuid' => Str::uuid(),
            'email' => 'provider@luca.com',
            'password_hash' => Hash::make('password'),
            'full_name' => 'FarmaRed S.A.',
            'phone' => '+584120000003',
            'role' => 'PROVIDER',
            'is_active' => true,
            'plan_type' => 'PRO',
            'city_id' => $cityId,
        ]);

        ProviderProfile::create([
            'user_id' => $providerUser->id,
            'type' => 'PHARMACY',
            'commercial_name' => 'Farmacia Red Las Mercedes',
            'rif' => 'J-31234567-8',
            'is_verified' => true,
        ]);

        // 4. Patient Account (Global Patient Account)
        $patientAccount = PatientAccount::create([
            'uuid' => Str::uuid(),
            'phone' => '+584141234567',
            'email' => 'patient@luca.com',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Juan Pérez',
            'national_id' => 'V-12345678',
            'username' => 'juanperez',
            'city_id' => $cityId,
        ]);

        // 5. Patient CRM Record (Clinical record owned by the Doctor)
        Patient::create([
            'uuid' => Str::uuid(),
            'user_id' => $doctor->id,
            'patient_account_id' => $patientAccount->id,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'national_id' => 'V-12345678',
            'birth_date' => '1990-05-15 00:00:00',
            'gender' => 'MALE',
            'email' => 'juan.perez@email.com',
            'phone' => '+584141234567',
            'address' => 'Avenida Principal de Las Mercedes, Edificio Altamira, Apto 4B',
            'city_id' => $cityId,
            'blood_type' => 'O+',
            'allergies' => 'Penicilina, Polen',
            'chronic_conditions' => 'Ninguna',
            'private_notes' => 'Paciente hipertenso leve en control. Buen estado general.',
        ]);
    }
}
