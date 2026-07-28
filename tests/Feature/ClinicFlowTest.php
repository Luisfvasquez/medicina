<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Models\HospitalizationRoom;
use App\Models\HospitalBed;
use App\Models\ClinicService;
use App\Models\SurgicalOperation;
use App\Models\Patient;
use App\Models\ClinicBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClinicFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createDependencies()
    {
        $country = \App\Models\Country::create(['uuid' => Str::uuid()->toString(), 'name' => 'Test', 'code' => 'TE']);
        $state = \App\Models\State::create(['uuid' => Str::uuid()->toString(), 'country_id' => $country->id, 'name' => 'Test']);
        $city = \App\Models\City::create(['uuid' => Str::uuid()->toString(), 'state_id' => $state->id, 'name' => 'Test']);
        $clinic = \App\Models\Clinic::create(['uuid' => Str::uuid()->toString(), 'name' => 'Test Clinic', 'slug' => 'test-clinic']);

        $branch = \App\Models\ClinicBranch::create([
            'uuid' => Str::uuid()->toString(),
            'clinic_id' => $clinic->id,
            'name' => 'Main Branch',
            'address' => '123 Main St',
            'city_id' => $city->id,
            'phone' => '123456789',
        ]);

        $account = \App\Models\PatientAccount::create([
            'uuid' => Str::uuid()->toString(),
            'email' => 'patient' . uniqid() . '@test.com',
            'phone' => '555' . rand(1000000, 9999999),
            'full_name' => 'John Doe',
            'password' => bcrypt('password'),
        ]);

        $role = \App\Models\ClinicRole::create([
            'clinic_branch_id' => $branch->id,
            'name' => 'Doctor'
        ]);

        return [
            'branch_id' => $branch->id,
            'branch_uuid' => $branch->uuid,
            'account_id' => $account->id,
            'role_id' => $role->id,
        ];
    }

    public function test_can_admit_patient_to_hospital_bed()
    {
        $deps = $this->createDependencies();
        $branchId = $deps['branch_id'];
        $branchUuid = $deps['branch_uuid'];
        
        $patient = \App\Models\Patient::create([
            'uuid' => Str::uuid()->toString(),
            'patient_account_id' => $deps['account_id'],
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'MALE',
            'date_of_birth' => '1990-01-01',
        ]);

        $room = HospitalizationRoom::create([
            'clinic_branch_id' => $branchId,
            'name' => 'Sala 1',
            'type' => 'general'
        ]);

        $bed = HospitalBed::create([
            'hospitalization_room_id' => $room->id,
            'bed_number' => '101A',
            'status' => 'available'
        ]);

        $response = $this->postJson("/api/v1/clinics/{$branchId}/admissions", [
            'patient_id' => $patient->id,
            'hospital_bed_id' => $bed->id,
            'admission_date' => now()->toDateTimeString(),
            'reason_for_admission' => 'Observación general',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'reason_for_admission' => 'Observación general'
                 ]);

        $this->assertDatabaseHas('hospital_beds', [
            'id' => $bed->id,
            'status' => 'occupied'
        ]);
        
        $this->assertDatabaseHas('hospital_admissions', [
            'patient_id' => $patient->id,
            'hospital_bed_id' => $bed->id,
            'status' => 'admitted'
        ]);
    }

    public function test_can_schedule_operation_and_assign_team()
    {
        $deps = $this->createDependencies();
        $branchId = $deps['branch_id'];
        $branchUuid = $deps['branch_uuid'];
        
        $patient = \App\Models\Patient::create([
            'uuid' => Str::uuid()->toString(),
            'patient_account_id' => $deps['account_id'],
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'FEMALE',
            'date_of_birth' => '1995-05-05',
        ]);

        $service = ClinicService::create([
            'clinic_branch_id' => $branchId,
            'name' => 'Apendicectomía',
            'base_price' => 1500,
            'type' => 'surgery'
        ]);
        
        $user = \App\Models\User::create([
            'full_name' => 'Doctor 1',
            'email' => 'doc' . uniqid() . '@test.com',
            'password_hash' => bcrypt('password'),
        ]);

        $staff = \App\Models\ClinicStaff::create([
            'clinic_branch_id' => $branchId,
            'clinic_role_id' => $deps['role_id'],
            'user_id' => $user->id,
        ]);

        $response = $this->postJson("/api/v1/clinics/{$branchId}/operations", [
            'patient_id' => $patient->id,
            'clinic_service_id' => $service->id,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'notes' => 'Paciente requiere anestesia general',
        ]);

        $response->assertStatus(201);
        $operationId = $response->json('id');

        $teamResponse = $this->postJson("/api/v1/clinics/{$branchId}/operations/{$operationId}/team", [
            'clinic_staff_id' => $staff->id,
            'role' => 'Cirujano Principal',
            'is_primary' => true,
        ]);

        $teamResponse->assertStatus(201)
                     ->assertJsonFragment([
                         'role' => 'Cirujano Principal'
                     ]);

        $this->assertDatabaseHas('surgical_operations', [
            'id' => $operationId,
            'status' => 'scheduled'
        ]);

        $this->assertDatabaseHas('surgical_teams', [
            'surgical_operation_id' => $operationId,
            'clinic_staff_id' => $staff->id,
            'is_primary' => true
        ]);
    }
}
