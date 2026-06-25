<?php

namespace Database\Seeders;

use App\Enums\Weekday;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicBranch;
use App\Models\ClinicBranchMember;
use App\Models\DoctorSchedule;
use App\Models\ProviderBranch;
use App\Models\ProviderProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $caracas = City::where('name', 'Caracas')->first();
        $losTeques = City::where('name', 'Los Teques')->first();
        $maracay = City::where('name', 'Maracay')->first();
        $valencia = City::where('name', 'Valencia')->first();
        $barquisimeto = City::where('name', 'Barquisimeto')->first();

        $specialties = Specialty::all()->keyBy('name');

        // =============================================================================
        // 10 DOCTORES VERIFICADOS
        // =============================================================================
        $doctors = [
            [
                'full_name' => 'Dra. María Fernanda Hernández',
                'email' => 'doctor.hernandez@luca.com',
                'phone' => '+584241111001',
                'specialties' => ['Medicina General', 'Medicina Familiar'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
            ],
            [
                'full_name' => 'Dr. Roberto Carlos Mendoza',
                'email' => 'doctor.mendoza@luca.com',
                'phone' => '+584241111002',
                'specialties' => ['Cardiología', 'Medicina Interna'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'TUESDAY', 'THURSDAY'],
            ],
            [
                'full_name' => 'Dra. Ana Lucía Pérez Vargas',
                'email' => 'doctor.perez@luca.com',
                'phone' => '+584241111003',
                'specialties' => ['Pediatría'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
            ],
            [
                'full_name' => 'Dr. Juan Pablo Martínez Ruiz',
                'email' => 'doctor.martinez@luca.com',
                'phone' => '+584241111004',
                'specialties' => ['Ginecología y Obstetricia'],
                'city' => $caracas,
                'schedule' => ['TUESDAY', 'THURSDAY', 'SATURDAY'],
            ],
            [
                'full_name' => 'Dra. Carolina Sofía Rodríguez',
                'email' => 'doctor.rodriguez@luca.com',
                'phone' => '+584241111005',
                'specialties' => ['Dermatología'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
            ],
            [
                'full_name' => 'Dr. Andrés Felipe García',
                'email' => 'doctor.garcia@luca.com',
                'phone' => '+584241111006',
                'specialties' => ['Traumatología y Ortopedia', 'Medicina del Trabajo'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY'],
            ],
            [
                'full_name' => 'Dra. Laura Beatriz Jiménez',
                'email' => 'doctor.jimenez@luca.com',
                'phone' => '+584241111007',
                'specialties' => ['Oftalmología'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
            ],
            [
                'full_name' => 'Dr. Miguel Ángel López Castro',
                'email' => 'doctor.lopez@luca.com',
                'phone' => '+584241111008',
                'specialties' => ['Neurología'],
                'city' => $caracas,
                'schedule' => ['TUESDAY', 'THURSDAY'],
            ],
            [
                'full_name' => 'Dra. Patricia Elena Muñoz',
                'email' => 'doctor.munoz@luca.com',
                'phone' => '+584241111009',
                'specialties' => ['Endocrinología', 'Medicina General'],
                'city' => $losTeques,
                'schedule' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
            ],
            [
                'full_name' => 'Dr. Fernando José Delgado',
                'email' => 'doctor.delgado@luca.com',
                'phone' => '+584241111010',
                'specialties' => ['Cirugía General', 'Medicina de Emergencia y Urgencias'],
                'city' => $caracas,
                'schedule' => ['MONDAY', 'TUESDAY', 'THURSDAY', 'FRIDAY'],
            ],
        ];

        $createdDoctors = [];
        foreach ($doctors as $doc) {
            // Check if user already exists by email
            $user = User::where('email', $doc['email'])->first();

            if (!$user) {
                $user = User::create([
                    'uuid' => Str::uuid(),
                    'email' => $doc['email'],
                    'password_hash' => Hash::make('password'),
                    'full_name' => $doc['full_name'],
                    'phone' => $doc['phone'],
                    'role' => 'DOCTOR',
                    'is_active' => true,
                    'plan_type' => 'PRO',
                    'city_id' => $doc['city']->id ?? null,
                    'logo_url' => 'https://luca-health.s3.amazonaws.com/avatars/doctors/' . Str::slug($doc['full_name']) . '.jpg',
                ]);

                // Attach specialties (idempotent - skip if already attached)
                $existingSpecs = $user->specialties->pluck('name')->toArray();
                foreach ($doc['specialties'] as $specName) {
                    if (isset($specialties[$specName]) && !in_array($specName, $existingSpecs)) {
                        $user->specialties()->attach($specialties[$specName]->id);
                    }
                }

                // Create doctor schedules (idempotent - skip if exists for this weekday)
                $existingSchedules = DoctorSchedule::where('user_id', $user->id)->pluck('weekday')->map(fn($w) => $w->value)->toArray();
                foreach ($doc['schedule'] as $weekday) {
                    if (!in_array($weekday, $existingSchedules)) {
                        DoctorSchedule::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $user->id,
                            'weekday' => Weekday::from($weekday),
                            'start_time' => '08:00',
                            'end_time' => '17:00',
                            'appointment_duration' => 30,
                            'max_per_slot' => 1,
                            'is_active' => true,
                        ]);
                    }
                }

                // Create verification document (APPROVED) for the doctor if not exists
                $hasApprovedDoc = VerificationDocument::where('user_id', $user->id)
                    ->where('status', 'APPROVED')
                    ->exists();
                if (!$hasApprovedDoc) {
                    VerificationDocument::create([
                        'uuid' => Str::uuid(),
                        'user_id' => $user->id,
                        'type' => 'MEDICAL_LICENSE',
                        'file_url' => 'https://luca-health.s3.amazonaws.com/verification/' . Str::slug($doc['full_name']) . '_license.pdf',
                        'status' => 'APPROVED',
                        'comments' => 'Documento verificado automáticamente',
                    ]);
                }
            }

            $createdDoctors[] = $user;
        }

        // =============================================================================
        // 10 FARMACIAS VERIFICADAS
        // =============================================================================
        $pharmacies = [
            [
                'name' => 'Farmacia San Juan',
                'rif' => 'J-40123456-1',
                'address' => 'Avenida Principal de Las Mercedes, Torre Médica, PB',
                'phone' => '+582129998800',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sucursal Las Mercedes',
                        'address' => 'Avenida Principal de Las Mercedes, Torre Médica, PB',
                        'phone' => '+582129998800',
                        'is_main_branch' => true,
                        'latitude' => 10.484200,
                        'longitude' => -66.862500,
                    ],
                    [
                        'name' => 'Sucursal Chacao',
                        'address' => 'Avenida Francisco de Miranda, Edificio Polar, Nivel 1',
                        'phone' => '+582129994422',
                        'is_main_branch' => false,
                        'latitude' => 10.491200,
                        'longitude' => -66.852900,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia CDP',
                'rif' => 'J-40123456-2',
                'address' => 'Avenida Libertador, Edificio CDP, PB',
                'phone' => '+582129887766',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Principal',
                        'address' => 'Avenida Libertador, Edificio CDP, PB',
                        'phone' => '+582129887766',
                        'is_main_branch' => true,
                        'latitude' => 10.500000,
                        'longitude' => -66.900000,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia de la Gente',
                'rif' => 'J-40123456-3',
                'address' => 'Calle real de Petare, Centro Comercial Petare, PB',
                'phone' => '+582129556677',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sucursal Petare',
                        'address' => 'Calle real de Petare, Centro Comercial Petare, PB',
                        'phone' => '+582129556677',
                        'is_main_branch' => true,
                        'latitude' => 10.470000,
                        'longitude' => -66.800000,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia Ciudad Medical',
                'rif' => 'J-40123456-4',
                'address' => 'Avenida ppal de El Cafetal, Centro Comercial Tolón, Nivel 2',
                'phone' => '+582129445566',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sucursal El Cafetal',
                        'address' => 'Avenida ppal de El Cafetal, Centro Comercial Tolón, Nivel 2',
                        'phone' => '+582129445566',
                        'is_main_branch' => true,
                        'latitude' => 10.505000,
                        'longitude' => -66.850000,
                    ],
                    [
                        'name' => 'Sucursal Terrazas',
                        'address' => 'Avenida ppal de Terrazas del Avila, Nivel Base',
                        'phone' => '+582129433355',
                        'is_main_branch' => false,
                        'latitude' => 10.510000,
                        'longitude' => -66.840000,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia Los Teques',
                'rif' => 'J-40123456-5',
                'address' => 'Avenida independencia, Centro Comercial los Teques, Nivel 1',
                'phone' => '+582129332244',
                'city' => $losTeques,
                'branches' => [
                    [
                        'name' => 'Sede Los Teques',
                        'address' => 'Avenida independencia, Centro Comercial los Teques, Nivel 1',
                        'phone' => '+582129332244',
                        'is_main_branch' => true,
                        'latitude' => 10.350000,
                        'longitude' => -67.050000,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia La Economía',
                'rif' => 'J-40123456-6',
                'address' => 'Avenida ppal de Maracay, Centro Comercial Maracay',
                'phone' => '+582431112233',
                'city' => $maracay,
                'branches' => [
                    [
                        'name' => 'Sucursal Maracay Centro',
                        'address' => 'Avenida ppal de Maracay, Centro Comercial Maracay',
                        'phone' => '+582431112233',
                        'is_main_branch' => true,
                        'latitude' => 10.246547,
                        'longitude' => -67.596757,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia Araguaney',
                'rif' => 'J-40123456-7',
                'address' => 'Avenida Bolívar Norte, Valencia',
                'phone' => '+582412231144',
                'city' => $valencia,
                'branches' => [
                    [
                        'name' => 'Sede Valencia Norte',
                        'address' => 'Avenida Bolívar Norte, Valencia',
                        'phone' => '+582412231144',
                        'is_main_branch' => true,
                        'latitude' => 10.162021,
                        'longitude' => -68.021024,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia Barquisimeto',
                'rif' => '+584251122334',
                'address' => 'Avenida ppal de Barquisimeto, Centro Comercial',
                'phone' => '+584251122334',
                'city' => $barquisimeto,
                'branches' => [
                    [
                        'name' => 'Sede Barquisimeto',
                        'address' => 'Avenida ppal de Barquisimeto, Centro Comercial',
                        'phone' => '+584251122334',
                        'is_main_branch' => true,
                        'latitude' => 10.064861,
                        'longitude' => -69.357046,
                    ],
                ],
            ],
            [
                'name' => 'Farmacia 24 Horas',
                'rif' => 'J-40123456-9',
                'address' => 'Avenida Libertador, Torre Financial Center, PB',
                'phone' => '+582129900011',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede 24 Horas Libertador',
                        'address' => 'Avenida Libertador, Torre Financial Center, PB',
                        'phone' => '+582129900011',
                        'is_main_branch' => true,
                        'latitude' => 10.502000,
                        'longitude' => -66.903000,
                        'observations' => 'Abierto 24 horas, todos los días',
                    ],
                ],
            ],
            [
                'name' => 'Farmacia del Este',
                'rif' => 'J-40123456-0',
                'address' => 'Avenida Francisco de Miranda, Centro Comercial Lido',
                'phone' => '+582129988877',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sucursal Lido',
                        'address' => 'Avenida Francisco de Miranda, Centro Comercial Lido',
                        'phone' => '+582129988877',
                        'is_main_branch' => true,
                        'latitude' => 10.493000,
                        'longitude' => -66.864000,
                    ],
                    [
                        'name' => 'Sucursal Concasa',
                        'address' => 'Avenida Principal de Bello Monte',
                        'phone' => '+582129977766',
                        'is_main_branch' => false,
                        'latitude' => 10.489000,
                        'longitude' => -66.873000,
                    ],
                ],
            ],
        ];

        foreach ($pharmacies as $pharmaData) {
            $pharmaEmail = 'provider.' . Str::slug($pharmaData['name']) . '@luca.com';
            $providerUser = User::where('email', $pharmaEmail)->first();

            if (!$providerUser) {
                $providerUser = User::create([
                    'uuid' => Str::uuid(),
                    'email' => $pharmaEmail,
                    'password_hash' => Hash::make('password'),
                    'full_name' => $pharmaData['name'],
                    'phone' => $pharmaData['phone'],
                    'role' => 'PROVIDER',
                    'is_active' => true,
                    'plan_type' => 'PRO',
                    'city_id' => $pharmaData['city']->id ?? null,
                ]);
            }

            $profile = ProviderProfile::where('user_id', $providerUser->id)->first();
            if (!$profile) {
                $profile = ProviderProfile::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $providerUser->id,
                    'type' => 'PHARMACY',
                    'commercial_name' => $pharmaData['name'],
                    'rif' => $pharmaData['rif'],
                    'address' => $pharmaData['address'],
                    'phone' => $pharmaData['phone'],
                    'city_id' => $pharmaData['city']->id ?? null,
                    'is_open' => true,
                    'is_verified' => true,
                ]);
            }

            foreach ($pharmaData['branches'] as $branchData) {
                $existingBranch = ProviderBranch::where('provider_profile_id', $profile->id)
                    ->where('name', $branchData['name'])
                    ->first();

                if (!$existingBranch) {
                    ProviderBranch::create([
                        'uuid' => Str::uuid(),
                        'provider_profile_id' => $profile->id,
                        'name' => $branchData['name'],
                        'address' => $branchData['address'],
                        'city_id' => $pharmaData['city']->id ?? null,
                        'phone' => $branchData['phone'],
                        'is_open' => true,
                        'is_main_branch' => $branchData['is_main_branch'],
                        'latitude' => $branchData['latitude'],
                        'longitude' => $branchData['longitude'],
                        'google_maps_url' => 'https://maps.google.com/?q=' . $branchData['latitude'] . ',' . $branchData['longitude'],
                        'observations' => $branchData['observations'] ?? null,
                    ]);
                }
            }
        }

        // =============================================================================
        // 10 CLÍNICAS VERIFICADAS
        // =============================================================================
        $clinics = [
            [
                'name' => 'Clínica Metropolitano de Especialidades',
                'rif' => 'J-30987654-1',
                'website' => 'https://clinicametropolitano.com.ve',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Las Mercedes',
                        'address' => 'Avenida Principal de Las Mercedes, Torre Corporativa, Piso 5',
                        'phone' => '+582129998877',
                        'is_main_branch' => true,
                        'latitude' => 10.484200,
                        'longitude' => -66.862500,
                    ],
                ],
            ],
            [
                'name' => 'Centro Médico La Floresta',
                'rif' => 'J-30987654-2',
                'website' => 'https://centromedicollafloresta.com',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Principal La Floresta',
                        'address' => 'Avenida ppal de La Floresta, Edificio CMO, Piso 2',
                        'phone' => '+582129955440',
                        'is_main_branch' => true,
                        'latitude' => 10.490000,
                        'longitude' => -66.858000,
                    ],
                ],
            ],
            [
                'name' => 'Instituto Médico del Este',
                'rif' => 'J-30987654-3',
                'website' => 'https://institutomedicodeleste.com.ve',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede El Cafetal',
                        'address' => 'Avenida ppal de El Cafetal, Centro Empresarial El Cafetal, Piso 3',
                        'phone' => '+582129933221',
                        'is_main_branch' => true,
                        'latitude' => 10.505000,
                        'longitude' => -66.850000,
                    ],
                    [
                        'name' => 'Sede San Román',
                        'address' => 'Avenida Principal de San Román, Consultorios San Román',
                        'phone' => '+582129922110',
                        'is_main_branch' => false,
                        'latitude' => 10.495000,
                        'longitude' => -66.855000,
                    ],
                ],
            ],
            [
                'name' => 'Hospital Veterinario y Clínica de Animales',
                'rif' => 'J-30987654-4',
                'website' => null,
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Principal',
                        'address' => 'Avenida ppal de Prados del Este, Centro Comercial Prados',
                        'phone' => '+582129911009',
                        'is_main_branch' => true,
                        'latitude' => 10.480000,
                        'longitude' => -66.840000,
                    ],
                ],
            ],
            [
                'name' => 'Clínica de la Mujer',
                'rif' => 'J-30987654-5',
                'website' => 'https://clinicaejecutivadelsur.com',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede del Valle',
                        'address' => 'Avenida Principal del Valle, Edificio del Valle Medical',
                        'phone' => '+582129900998',
                        'is_main_branch' => true,
                        'latitude' => 10.465000,
                        'longitude' => -66.880000,
                    ],
                ],
            ],
            [
                'name' => 'Centro de Especialidades Pediatricas',
                'rif' => 'J-30987654-6',
                'website' => 'https://cep Venezuela.com.ve',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Los Chaguaramos',
                        'address' => 'Avenida Principal de Los Chaguaramos, Centro Comercial Chaguaramos',
                        'phone' => '+582129889900',
                        'is_main_branch' => true,
                        'latitude' => 10.470000,
                        'longitude' => -66.870000,
                    ],
                ],
            ],
            [
                'name' => 'Instituto Cardiovascular de Venezuela',
                'rif' => 'J-30987654-7',
                'website' => 'https://icardio.com.ve',
                'city' => $caracas,
                'branches' => [
                    [
                        'name' => 'Sede Central',
                        'address' => 'Avenida Libertador, Torre Cardiovascular, Piso 8',
                        'phone' => '+582129878766',
                        'is_main_branch' => true,
                        'latitude' => 10.500000,
                        'longitude' => -66.900000,
                    ],
                ],
            ],
            [
                'name' => 'Clínica Los Teques',
                'rif' => 'J-30987654-8',
                'website' => 'https://cliniclosteques.com.ve',
                'city' => $losTeques,
                'branches' => [
                    [
                        'name' => 'Sede Los Teques Centro',
                        'address' => 'Avenida independencia, Edificio Los Teques Center',
                        'phone' => '+582129767655',
                        'is_main_branch' => true,
                        'latitude' => 10.350000,
                        'longitude' => -67.050000,
                    ],
                ],
            ],
            [
                'name' => 'Centro Médico de Maracay',
                'rif' => 'J-30987654-9',
                'website' => 'https://centromaracay.com.ve',
                'city' => $maracay,
                'branches' => [
                    [
                        'name' => 'Sede Maracay',
                        'address' => 'Avenida Bolívar, Centro Comercial Bolivarium',
                        'phone' => '+582431665544',
                        'is_main_branch' => true,
                        'latitude' => 10.246547,
                        'longitude' => -67.596757,
                    ],
                ],
            ],
            [
                'name' => 'Instituto Valenciano de Oftalmología',
                'rif' => 'J-30987654-0',
                'website' => 'https://ivogtt.com',
                'city' => $valencia,
                'branches' => [
                    [
                        'name' => 'Sede Valencia',
                        'address' => 'Avenida Bolívar Norte, Centro Comercial ophthalmology',
                        'phone' => '+582412334455',
                        'is_main_branch' => true,
                        'latitude' => 10.162021,
                        'longitude' => -68.021024,
                    ],
                ],
            ],
        ];

        // Associate first 5 doctors to clinics (2 doctors per clinic)
        $doctorIndex = 0;
        foreach ($clinics as $clinicData) {
            $clinic = Clinic::where('rif', $clinicData['rif'])->first();
            if (!$clinic) {
                $clinic = Clinic::create([
                    'uuid' => Str::uuid(),
                    'name' => $clinicData['name'],
                    'rif' => $clinicData['rif'],
                    'logo_url' => 'https://luca-health.s3.amazonaws.com/logos/clinics/' . Str::slug($clinicData['name']) . '.png',
                    'website' => $clinicData['website'],
                ]);
            }

            foreach ($clinicData['branches'] as $branchData) {
                $branch = ClinicBranch::where('clinic_id', $clinic->id)
                    ->where('name', $branchData['name'])
                    ->first();

                if (!$branch) {
                    $branch = ClinicBranch::create([
                        'uuid' => Str::uuid(),
                        'clinic_id' => $clinic->id,
                        'name' => $branchData['name'],
                        'address' => $branchData['address'],
                        'city_id' => $clinicData['city']->id ?? null,
                        'phone' => $branchData['phone'],
                        'is_main_branch' => $branchData['is_main_branch'],
                        'latitude' => $branchData['latitude'],
                        'longitude' => $branchData['longitude'],
                        'google_maps_url' => 'https://maps.google.com/?q=' . $branchData['latitude'] . ',' . $branchData['longitude'],
                    ]);
                }

                // Associate 2 doctors to this branch
                if ($doctorIndex < count($createdDoctors)) {
                    $existingMember = ClinicBranchMember::where('clinic_branch_id', $branch->id)
                        ->where('user_id', $createdDoctors[$doctorIndex]->id)
                        ->first();

                    if (!$existingMember) {
                        ClinicBranchMember::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $createdDoctors[$doctorIndex]->id,
                            'clinic_branch_id' => $branch->id,
                            'role' => 'DOCTOR',
                            'department' => $createdDoctors[$doctorIndex]->specialties->first()?->name ?? 'Medicina General',
                            'office_number' => 'Consultorio ' . ($doctorIndex * 101 + 100),
                            'is_active' => true,
                        ]);
                    }
                    $doctorIndex++;
                }

                if ($doctorIndex < count($createdDoctors)) {
                    $existingMember = ClinicBranchMember::where('clinic_branch_id', $branch->id)
                        ->where('user_id', $createdDoctors[$doctorIndex]->id)
                        ->first();

                    if (!$existingMember) {
                        ClinicBranchMember::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $createdDoctors[$doctorIndex]->id,
                            'clinic_branch_id' => $branch->id,
                            'role' => 'DOCTOR',
                            'department' => $createdDoctors[$doctorIndex]->specialties->first()?->name ?? 'Medicina General',
                            'office_number' => 'Consultorio ' . ($doctorIndex * 101 + 101),
                            'is_active' => true,
                        ]);
                    }
                    $doctorIndex++;
                }
            }
        }
    }
}
