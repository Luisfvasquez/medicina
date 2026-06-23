<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Clinic;
use App\Models\ClinicBranch;
use App\Models\ClinicBranchMember;
use App\Models\ProviderBranch;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Models\City;
use Illuminate\Support\Str;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $caracas = City::where('name', 'Caracas')->first();
        $cityId = $caracas ? $caracas->id : null;

        // 1. Clinic (Institutional)
        $clinic = Clinic::create([
            'uuid' => Str::uuid(),
            'name' => 'Clínica Metropolitana de Especialidades',
            'rif' => 'J-45678901-2',
            'logo_url' => 'https://luca-health.s3.amazonaws.com/logos/clinic_demo.png',
            'website' => 'https://clinicametropolitana.com.ve',
        ]);

        // 2. Clinic Branches (Sedes físicas)
        $mainBranch = ClinicBranch::create([
            'uuid' => Str::uuid(),
            'clinic_id' => $clinic->id,
            'name' => 'Sede Las Mercedes',
            'address' => 'Avenida Principal de Las Mercedes, Calle Monterrey, Torre Financiera, Piso 3',
            'city_id' => $cityId,
            'phone' => '+582129998877',
            'is_main_branch' => true,
            'latitude' => 10.48420000,
            'longitude' => -66.86250000,
            'google_maps_url' => 'https://maps.google.com/?q=10.4842,-66.8625',
            'observations' => 'Estacionamiento privado disponible. Sede central de cardiología y pediatría.',
        ]);

        $subBranch = ClinicBranch::create([
            'uuid' => Str::uuid(),
            'clinic_id' => $clinic->id,
            'name' => 'Anexo Chacao',
            'address' => 'Avenida Francisco de Miranda, Edificio Multicentro Empresarial, local M1',
            'city_id' => $cityId,
            'phone' => '+582125554433',
            'is_main_branch' => false,
            'latitude' => 10.49120000,
            'longitude' => -66.85290000,
            'google_maps_url' => 'https://maps.google.com/?q=10.4912,-66.8529',
            'observations' => 'Solo consultas preventivas y laboratorios básicos.',
        ]);

        // 3. Link Doctor to Main Clinic Branch
        $doctor = User::where('email', 'doctor@luca.com')->first();
        if ($doctor) {
            ClinicBranchMember::create([
                'uuid' => Str::uuid(),
                'user_id' => $doctor->id,
                'clinic_branch_id' => $mainBranch->id,
                'role' => 'DOCTOR',
                'department' => 'Cardiología y Medicina General',
                'office_number' => 'Consultorio 301',
                'is_active' => true,
            ]);
        }

        // 4. Provider Branch (Sucursal física de la farmacia)
        $providerProfile = ProviderProfile::first();
        if ($providerProfile) {
            ProviderBranch::create([
                'uuid' => Str::uuid(),
                'provider_profile_id' => $providerProfile->id,
                'name' => 'Farmacia Red Las Mercedes - Sede Principal',
                'address' => 'Avenida Principal de Las Mercedes, Centro Comercial Paseo Las Mercedes, Planta Baja, Local 15',
                'city_id' => $cityId,
                'phone' => '+582128887766',
                'is_open' => true,
                'is_main_branch' => true,
                'latitude' => 10.48200000,
                'longitude' => -66.86400000,
                'google_maps_url' => 'https://maps.google.com/?q=10.4820,-66.8640',
                'observations' => 'Abierto las 24 horas del día. Auto-servicio disponible.',
            ]);
        }
    }
}
