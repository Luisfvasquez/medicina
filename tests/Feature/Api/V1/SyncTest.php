<?php

use App\Models\Appointment;
use App\Models\City;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalBackground;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\QuoteOffer;
use App\Models\QuoteRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createAuthUser(): User
{
    return User::create([
        'full_name'     => 'Dr. Test',
        'email'         => 'dr@test.com',
        'password_hash' => bcrypt('password'),
        'role'          => 'DOCTOR',
        'is_active'     => true,
        'phone'         => '+580001234567',
    ]);
}

function seedPatient(User $user, array $overrides = []): Patient
{
    $account = PatientAccount::create([
        'email'         => ($overrides['email'] ?? 'test') . '@patient.test',
        'password_hash' => bcrypt('password'),
        'full_name'     => ($overrides['first_name'] ?? 'Test') . ' ' . ($overrides['last_name'] ?? 'Patient'),
        'phone'         => '+580001234568',
    ]);

    return Patient::create(array_merge([
        'user_id'            => $user->id,
        'patient_account_id' => $account->id,
        'first_name'         => 'Test',
        'last_name'          => 'Patient',
        'birth_date'         => '2000-01-01',
        'updated_at'         => Carbon::now(),
    ], $overrides));
}

function buildPatientPayload(string $uuid, array $overrides = []): array
{
    return array_merge([
        'uuid'                  => $uuid,
        'first_name'            => 'Juan',
        'last_name'             => 'Perez',
        'email'                 => 'juan@test.com',
        'phone'                 => '+584121234567',
        'birth_date'            => '1990-01-15',
        'updated_at'            => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildAppointmentPayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'date'         => Carbon::tomorrow()->toDateString(),
        'time'         => '10:00',
        'type'         => 'CONSULTA',
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildConsultationPayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'date'         => Carbon::now()->toDateString(),
        'reason'       => 'Control',
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildMedicalBackgroundPayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'has_diabetes' => false,
        'has_hypertension' => true,
        'has_asthma'   => false,
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildPrescriptionPayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'date'         => Carbon::now()->toDateString(),
        'status'       => 'ACTIVE',
        'notes'        => 'Take with food',
        'public_token' => 'tok-' . $uuid,
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildPrescriptionItemPayload(string $uuid, string $prescriptionUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'              => $uuid,
        'prescription_uuid' => $prescriptionUuid,
        'dose'              => '500mg',
        'frequency'         => 'Every 8 hours',
        'duration'          => '7 days',
        'quantity'          => 21,
        'updated_at'        => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildInvoicePayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'subtotal'     => 100.00,
        'tax'          => 16.00,
        'total'        => 116.00,
        'currency'     => 'USD',
        'status'       => 'DRAFT',
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildInvoiceItemPayload(string $uuid, string $invoiceUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'invoice_uuid' => $invoiceUuid,
        'description'  => 'Consultation fee',
        'quantity'     => 1,
        'unit_price'   => 100.00,
        'total'        => 100.00,
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildPaymentPayload(string $uuid, string $invoiceUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'invoice_uuid' => $invoiceUuid,
        'amount'       => 50.00,
        'method'       => 'CASH',
        'paid_at'      => Carbon::now()->toISOString(),
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildQuoteRequestPayload(string $uuid, string $patientUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'         => $uuid,
        'patient_uuid' => $patientUuid,
        'status'       => 'OPEN',
        'updated_at'   => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildQuoteOfferPayload(string $uuid, string $quoteRequestUuid, array $overrides = []): array
{
    return array_merge([
        'uuid'              => $uuid,
        'quote_request_uuid' => $quoteRequestUuid,
        'price'             => 25.00,
        'currency'          => 'USD',
        'comments'          => 'Available next week',
        'provider_id'       => 1,
        'updated_at'        => Carbon::now()->toISOString(),
    ], $overrides);
}

function buildMedicationPayload(string $uuid, array $overrides = []): array
{
    return array_merge([
        'uuid'               => $uuid,
        'active_principle'   => 'Paracetamol',
        'concentration'      => '500mg',
        'presentation'       => 'Tablets',
        'administration_route' => 'Oral',
        'commercial_name'    => 'Tylenol',
        'is_active'          => true,
        'updated_at'         => Carbon::now()->toISOString(),
    ], $overrides);
}

// ---------------------------------------------------------------------------
//  Authentication
// ---------------------------------------------------------------------------

test('sync requires authentication', function () {
    $response = $this->postJson('/api/v1/sync', []);

    $response->assertStatus(401);
});

// ---------------------------------------------------------------------------
//  Push: patients
// ---------------------------------------------------------------------------

test('push creates a new patient', function () {
    $user    = createAuthUser();
    $uuid    = 'a0000001-0000-4000-8000-000000000001';
    $payload = [
        'push' => [
            'patients' => [
                buildPatientPayload($uuid, ['first_name' => 'Ana', 'last_name' => 'Lopez']),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success.0', $uuid)
        ->assertJsonPath('push_results.patients.errors', []);

    expect(Patient::where('uuid', $uuid)->exists())->toBeTrue();
});

test('push creates multiple patients', function () {
    $user    = createAuthUser();
    $uuid1   = 'a0000001-0000-4000-8000-000000000001';
    $uuid2   = 'a0000001-0000-4000-8000-000000000002';
    $payload = [
        'push' => [
            'patients' => [
                buildPatientPayload($uuid1),
                buildPatientPayload($uuid2),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success', [$uuid1, $uuid2]);
});

// ---------------------------------------------------------------------------
//  LWW: Last-Write-Wins
// ---------------------------------------------------------------------------

test('lww accepts newer client version', function () {
    $user = createAuthUser();
    $uuid = 'a0000001-0000-4000-8000-000000000010';

    // Seed server record with an older timestamp
    seedPatient($user, [
        'uuid'        => $uuid,
        'first_name'  => 'Old',
        'last_name'   => 'Name',
        'updated_at'  => Carbon::now()->subHour(),
    ]);

    $payload = [
        'push' => [
            'patients' => [
                buildPatientPayload($uuid, [
                    'first_name' => 'New',
                    'updated_at' => Carbon::now()->toISOString(),
                ]),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success.0', $uuid);

    expect(Patient::where('uuid', $uuid)->first()->first_name)->toBe('New');
});

test('lww rejects older client version', function () {
    $user = createAuthUser();
    $uuid = 'a0000001-0000-4000-8000-000000000011';

    seedPatient($user, [
        'uuid'        => $uuid,
        'first_name'  => 'ServerName',
        'last_name'   => 'ServerLast',
        'updated_at'  => Carbon::now(),
    ]);

    $payload = [
        'push' => [
            'patients' => [
                buildPatientPayload($uuid, [
                    'first_name' => 'ClientName',
                    'updated_at' => Carbon::now()->subHour()->toISOString(),
                ]),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success', [])
        ->assertJsonPath('push_results.patients.errors.0.field', 'updated_at');

    expect(Patient::where('uuid', $uuid)->first()->first_name)->toBe('ServerName');
});

// ---------------------------------------------------------------------------
//  Appointments (patient_uuid resolution)
// ---------------------------------------------------------------------------

test('push creates appointment with patient_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'a0000001-0000-4000-8000-000000000020';

    // Patient must exist
    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Carlos', 'last_name' => 'Ruiz', 'updated_at' => Carbon::now()]);

    $apptUuid = 'a0000001-0000-4000-8000-000000000021';
    $payload  = [
        'push' => [
            'appointments' => [
                buildAppointmentPayload($apptUuid, $patientUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.appointments.success.0', $apptUuid);

    expect(Appointment::where('uuid', $apptUuid)->exists())->toBeTrue();
});

test('push reports error when patient_uuid not found for appointment', function () {
    $user   = createAuthUser();
    $apptUuid = 'a0000001-0000-4000-8000-000000000030';
    $payload = [
        'push' => [
            'appointments' => [
                buildAppointmentPayload($apptUuid, 'nonexistent-uuid'),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.appointments.success', [])
        ->assertJsonPath('push_results.appointments.errors.0.uuid', $apptUuid)
        ->assertJsonPath('push_results.appointments.errors.0.field', 'patient_uuid');
});

// ---------------------------------------------------------------------------
//  Consultations
// ---------------------------------------------------------------------------

test('push creates consultation', function () {
    $user        = createAuthUser();
    $patientUuid = 'a0000001-0000-4000-8000-000000000040';

    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Maria', 'last_name' => 'Gomez', 'updated_at' => Carbon::now()]);

    $consultUuid = 'a0000001-0000-4000-8000-000000000041';
    $payload     = [
        'push' => [
            'consultations' => [
                buildConsultationPayload($consultUuid, $patientUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.consultations.success.0', $consultUuid);

    expect(Consultation::where('uuid', $consultUuid)->exists())->toBeTrue();
});

test('push reports error when patient_uuid not found for consultation', function () {
    $user     = createAuthUser();
    $consultUuid = 'a0000001-0000-4000-8000-000000000050';
    $payload  = [
        'push' => [
            'consultations' => [
                buildConsultationPayload($consultUuid, 'nonexistent-uuid'),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.consultations.success', [])
        ->assertJsonPath('push_results.consultations.errors.0.uuid', $consultUuid)
        ->assertJsonPath('push_results.consultations.errors.0.field', 'patient_uuid');
});

// ---------------------------------------------------------------------------
//  Selective failure: FK violation does not abort the whole batch
// ---------------------------------------------------------------------------

test('selective failure: valid records saved even when one fails FK', function () {
    $user  = createAuthUser();
    $uuid1 = 'a0000001-0000-4000-8000-000000000060';
    $uuid2 = 'a0000001-0000-4000-8000-000000000061';

    $payload = [
        'push' => [
            'patients' => [
                buildPatientPayload($uuid1),
                // Bad city_id — does not exist
                buildPatientPayload($uuid2, ['city_id' => 99999]),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success.0', $uuid1)
        ->assertJsonPath('push_results.patients.errors.0.uuid', $uuid2);

    expect(Patient::where('uuid', $uuid1)->exists())->toBeTrue();
    expect(Patient::where('uuid', $uuid2)->exists())->toBeFalse();
});

// ---------------------------------------------------------------------------
//  Topological order: patients pushed in same batch used for appointments
// ---------------------------------------------------------------------------

test('topological order: appointment references patient from same batch', function () {
    $user        = createAuthUser();
    $patientUuid = 'a0000001-0000-4000-8000-000000000070';
    $apptUuid    = 'a0000001-0000-4000-8000-000000000071';

    $payload = [
        'push' => [
            'patients'     => [buildPatientPayload($patientUuid)],
            'appointments' => [buildAppointmentPayload($apptUuid, $patientUuid)],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success.0', $patientUuid)
        ->assertJsonPath('push_results.appointments.success.0', $apptUuid);

    $patient = Patient::where('uuid', $patientUuid)->first();
    expect(Appointment::where('uuid', $apptUuid)->first()->patient_id)->toBe($patient->id);
});

// ---------------------------------------------------------------------------
//  Pull
// ---------------------------------------------------------------------------

test('pull returns records updated after last_sync_timestamp', function () {
    $user = createAuthUser();
    $uuid = 'a0000001-0000-4000-8000-000000000080';

    seedPatient($user, [
        'uuid'       => $uuid,
        'first_name' => 'PullTest',
        'last_name'  => 'Patient',
        'updated_at' => Carbon::now()->subMinutes(10),
    ]);

    $lastSync = Carbon::now()->subMinutes(15);

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', [
        'last_sync_timestamp' => $lastSync->toISOString(),
        'push'                => [],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'pull.patients')
        ->assertJsonPath('pull.patients.0.uuid', $uuid);
});

test('pull does not return records older than last_sync_timestamp', function () {
    $user = createAuthUser();
    $uuid = 'a0000001-0000-4000-8000-000000000090';

    $patientTime = '2026-06-23 10:00:00';
    $lastSync    = '2026-06-23 11:00:00';

    $p = seedPatient($user, [
        'uuid'       => $uuid,
        'first_name' => 'OldPatient',
        'last_name'  => 'Test',
        'updated_at' => $patientTime,
        'created_at' => $patientTime,
    ]);

    // Force the stored timestamps to our desired values
    $p->timestamps = false;
    $p->updated_at = $patientTime;
    $p->created_at = $patientTime;
    $p->save(['timestamps' => false]);

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', [
        'last_sync_timestamp' => $lastSync,
        'push'                => [],
    ]);

    $response->assertOk()
        ->assertJsonCount(0, 'pull.patients');
});

test('pull includes patient_uuid for appointments', function () {
    $user        = createAuthUser();
    $patientUuid = 'a0000001-0000-4000-8000-000000000100';
    $apptUuid    = 'a0000001-0000-4000-8000-000000000101';

    $patient = seedPatient($user, [
        'uuid'       => $patientUuid,
        'first_name' => 'P',
        'last_name'  => 'T',
        'updated_at' => Carbon::now()->subMinutes(5),
    ]);

    Appointment::create([
        'uuid'       => $apptUuid,
        'patient_id' => $patient->id,
        'user_id'    => $user->id,
        'date'       => Carbon::tomorrow(),
        'time'       => '09:00',
        'type'       => 'CONSULTA',
        'updated_at' => Carbon::now()->subMinutes(2),
    ]);

    $lastSync = Carbon::now()->subMinutes(10);

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', [
        'last_sync_timestamp' => $lastSync->toISOString(),
        'push'                => [],
    ]);

    $response->assertOk()
        ->assertJsonPath('pull.appointments.0.uuid', $apptUuid)
        ->assertJsonPath('pull.appointments.0.patient_uuid', $patientUuid);
});

test('pull has_more is false when under limit', function () {
    $user = createAuthUser();
    $lastSync = Carbon::now()->subMinutes(60);

    // Create just 1 patient
    seedPatient($user, [
        'uuid'       => 'a0000001-0000-4000-8000-000000000200',
        'first_name' => 'Single',
        'last_name'  => 'Record',
        'updated_at' => Carbon::now()->subMinutes(5),
    ]);

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', [
        'last_sync_timestamp' => $lastSync->toISOString(),
        'push'                => [],
    ]);

    $response->assertOk()
        ->assertJsonPath('has_more', false);
});

// ---------------------------------------------------------------------------
//  Full sync: push + pull in one request
// ---------------------------------------------------------------------------

test('full sync: push patients and pull server changes in one call', function () {
    $user = createAuthUser();
    $patientUuid = 'a0000001-0000-4000-8000-000000000300';

    // Seed a server-side patient that was updated after the client's last sync
    $lastSync = Carbon::now()->subMinutes(20);
    seedPatient($user, [
        'uuid'       => 'a0000001-0000-4000-8000-000000000301',
        'first_name' => 'Server',
        'last_name'  => 'Side',
        'updated_at' => Carbon::now()->subMinutes(5),
    ]);

    $payload = [
        'last_sync_timestamp' => $lastSync->toISOString(),
        'push' => [
            'patients' => [buildPatientPayload($patientUuid)],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.patients.success.0', $patientUuid)
        ->assertJsonPath('sync_timestamp', fn ($ts) => ! empty($ts))
        ->assertJsonPath('has_more', false)
        // Should pull the server-side patient
        ->assertJsonPath('pull.patients.0.uuid', 'a0000001-0000-4000-8000-000000000301');
});

// ---------------------------------------------------------------------------
//  Validation
// ---------------------------------------------------------------------------

test('sync validates uuid is required for push items', function () {
    $user    = createAuthUser();
    $payload = [
        'push' => [
            'patients' => [
                ['first_name' => 'NoUuid', 'updated_at' => Carbon::now()->toISOString()],
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['push.patients.0.uuid']);
});

test('sync validates updated_at is required for push items', function () {
    $user    = createAuthUser();
    $payload = [
        'push' => [
            'patients' => [
                ['uuid' => 'a0000001-0000-4000-8000-000000000400', 'first_name' => 'NoTs'],
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['push.patients.0.updated_at']);
});

// ---------------------------------------------------------------------------
//  Push: medical_backgrounds (patient_uuid FK resolution)
// ---------------------------------------------------------------------------

test('push creates medical_background with patient_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'b0000001-0000-4000-8000-000000000001';
    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Bg', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    $bgUuid = 'b0000001-0000-4000-8000-000000000002';
    $payload = [
        'push' => [
            'medical_backgrounds' => [
                buildMedicalBackgroundPayload($bgUuid, $patientUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.medical_backgrounds.success.0', $bgUuid);

    expect(MedicalBackground::where('uuid', $bgUuid)->exists())->toBeTrue();
});

test('push reports error when patient_uuid not found for medical_background', function () {
    $user  = createAuthUser();
    $bgUuid = 'b0000001-0000-4000-8000-000000000003';
    $payload = [
        'push' => [
            'medical_backgrounds' => [
                buildMedicalBackgroundPayload($bgUuid, 'nonexistent-uuid'),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.medical_backgrounds.success', [])
        ->assertJsonPath('push_results.medical_backgrounds.errors.0.uuid', $bgUuid)
        ->assertJsonPath('push_results.medical_backgrounds.errors.0.field', 'patient_uuid');
});

// ---------------------------------------------------------------------------
//  Push: prescriptions
// ---------------------------------------------------------------------------

test('push creates prescription with patient_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'b0000001-0000-4000-8000-000000000010';
    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Rx', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    $rxUuid = 'b0000001-0000-4000-8000-000000000011';
    $payload = [
        'push' => [
            'prescriptions' => [
                buildPrescriptionPayload($rxUuid, $patientUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.prescriptions.success.0', $rxUuid);

    expect(Prescription::where('uuid', $rxUuid)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
//  Push: prescription_items (nested under prescription)
// ---------------------------------------------------------------------------

test('push creates prescription_item with prescription_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'b0000001-0000-4000-8000-000000000020';
    $patient     = seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'RxI', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    // Seed a prescription
    $rxUuid = 'b0000001-0000-4000-8000-000000000021';
    Prescription::create([
        'uuid'         => $rxUuid,
        'user_id'      => $user->id,
        'patient_id'   => $patient->id,
        'date'         => Carbon::now(),
        'status'       => 'ACTIVE',
        'public_token' => 'tok-rxi',
    ]);

    $itemUuid = 'b0000001-0000-4000-8000-000000000022';
    $payload = [
        'push' => [
            'prescription_items' => [
                buildPrescriptionItemPayload($itemUuid, $rxUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.prescription_items.success.0', $itemUuid);

    expect(PrescriptionItem::where('uuid', $itemUuid)->exists())->toBeTrue();
});

test('push reports error when prescription_uuid not found for prescription_item', function () {
    $user     = createAuthUser();
    $itemUuid = 'b0000001-0000-4000-8000-000000000023';
    $payload  = [
        'push' => [
            'prescription_items' => [
                buildPrescriptionItemPayload($itemUuid, 'nonexistent-uuid'),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.prescription_items.success', [])
        ->assertJsonPath('push_results.prescription_items.errors.0.uuid', $itemUuid)
        ->assertJsonPath('push_results.prescription_items.errors.0.field', 'prescription_uuid');
});

// ---------------------------------------------------------------------------
//  Pull: cities (read-only catalog)
// ---------------------------------------------------------------------------

test('pull returns cities catalog', function () {
    $user = createAuthUser();

    // Seed FK chain using DB facade (bypasses fillable)
    \Illuminate\Support\Facades\DB::table('countries')->insert(['id' => 1, 'uuid' => 'd0000001-0000-4000-8000-000000000001', 'name' => 'Venezuela', 'code' => 'VE']);
    \Illuminate\Support\Facades\DB::table('states')->insert(['id' => 1, 'uuid' => 'd0000001-0000-4000-8000-000000000002', 'name' => 'Distrito Capital', 'country_id' => 1]);

    City::create(['uuid' => 'c0000001-0000-4000-8000-000000000001', 'name' => 'Caracas', 'state_id' => 1, 'updated_at' => Carbon::now()->subMinutes(5)]);
    City::create(['uuid' => 'c0000001-0000-4000-8000-000000000002', 'name' => 'Maracaibo', 'state_id' => 1, 'updated_at' => Carbon::now()->subMinutes(3)]);

    $lastSync = Carbon::now()->subMinutes(10);

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', [
        'last_sync_timestamp' => $lastSync->toISOString(),
        'push'                => [],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'pull.cities')
        ->assertJsonPath('pull.cities.0.name', 'Caracas')
        ->assertJsonPath('pull.cities.1.name', 'Maracaibo');
});

// ---------------------------------------------------------------------------
//  Push: medications (doctor can push medications)
// ---------------------------------------------------------------------------

test('push creates medication', function () {
    $user    = createAuthUser();
    $medUuid = 'c0000001-0000-4000-8000-000000000010';
    $payload = [
        'push' => [
            'medications' => [
                buildMedicationPayload($medUuid, ['commercial_name' => 'Amoxicilina']),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.medications.success.0', $medUuid);
});

// ---------------------------------------------------------------------------
//  Push: invoices → invoice_items → payments
// ---------------------------------------------------------------------------

test('push creates invoice with patient_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'c0000001-0000-4000-8000-000000000020';
    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Inv', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    $invUuid = 'c0000001-0000-4000-8000-000000000021';
    $payload = [
        'push' => [
            'invoices' => [
                buildInvoicePayload($invUuid, $patientUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.invoices.success.0', $invUuid);

    expect(Invoice::where('uuid', $invUuid)->exists())->toBeTrue();
});

test('push creates invoice_item with invoice_uuid resolution from same batch', function () {
    $user        = createAuthUser();
    $patientUuid = 'c0000001-0000-4000-8000-000000000030';
    seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'InvI', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    $invUuid  = 'c0000001-0000-4000-8000-000000000031';
    $itemUuid = 'c0000001-0000-4000-8000-000000000032';

    $payload = [
        'push' => [
            'invoices'      => [buildInvoicePayload($invUuid, $patientUuid)],
            'invoice_items' => [buildInvoiceItemPayload($itemUuid, $invUuid)],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.invoices.success.0', $invUuid)
        ->assertJsonPath('push_results.invoice_items.success.0', $itemUuid);

    expect(InvoiceItem::where('uuid', $itemUuid)->exists())->toBeTrue();
    $inv = Invoice::where('uuid', $invUuid)->first();
    expect(InvoiceItem::where('uuid', $itemUuid)->first()->invoice_id)->toBe($inv->id);
});

test('push creates payment with invoice_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'c0000001-0000-4000-8000-000000000040';
    $patient     = seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Pay', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    // Seed an existing invoice
    $invUuid = 'c0000001-0000-4000-8000-000000000041';
    Invoice::create([
        'uuid'       => $invUuid,
        'user_id'    => $user->id,
        'patient_id' => $patient->id,
        'subtotal'   => 100.00,
        'tax'        => 16.00,
        'total'      => 116.00,
        'currency'   => 'USD',
        'status'     => 'DRAFT',
    ]);

    $payUuid = 'c0000001-0000-4000-8000-000000000042';
    $payload = [
        'push' => [
            'payments' => [
                buildPaymentPayload($payUuid, $invUuid),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.payments.success.0', $payUuid);

    expect(Payment::where('uuid', $payUuid)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
//  Push: quote_requests → quote_offers
// ---------------------------------------------------------------------------

test('push creates quote_request with patient_uuid resolution', function () {
    $user        = createAuthUser();
    $patientUuid = 'c0000001-0000-4000-8000-000000000050';
    $patient     = seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Qr', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    // Seed a country/state/city chain
    \Illuminate\Support\Facades\DB::table('countries')->insert(['id' => 1, 'uuid' => 'qr-cc-01', 'name' => 'VE', 'code' => 'VE']);
    \Illuminate\Support\Facades\DB::table('states')->insert(['id' => 1, 'uuid' => 'qr-st-01', 'name' => 'DC', 'country_id' => 1]);
    \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 1, 'uuid' => 'qr-ct-01', 'name' => 'Caracas', 'state_id' => 1]);

    // Seed a prescription (required FK)
    $rxUuid = 'c0000001-0000-4000-8000-000000000049';
    Prescription::create([
        'uuid'         => $rxUuid,
        'user_id'      => $user->id,
        'patient_id'   => $patient->id,
        'date'         => Carbon::now(),
        'status'       => 'ACTIVE',
        'public_token' => 'tok-qr',
    ]);

    $qrUuid = 'c0000001-0000-4000-8000-000000000051';
    $payload = [
        'push' => [
            'quote_requests' => [
                buildQuoteRequestPayload($qrUuid, $patientUuid, ['prescription_uuid' => $rxUuid, 'city_id' => 1]),
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.quote_requests.success.0', $qrUuid);

    expect(QuoteRequest::where('uuid', $qrUuid)->exists())->toBeTrue();
});

test('push creates quote_offer with quote_request_uuid resolution from same batch', function () {
    $user        = createAuthUser();
    $patientUuid = 'c0000001-0000-4000-8000-000000000060';
    $patient     = seedPatient($user, ['uuid' => $patientUuid, 'first_name' => 'Qo', 'last_name' => 'Test', 'updated_at' => Carbon::now()]);

    // Seed a country/state/city chain
    \Illuminate\Support\Facades\DB::table('countries')->insert(['id' => 1, 'uuid' => 'qo-cc-01', 'name' => 'VE', 'code' => 'VE']);
    \Illuminate\Support\Facades\DB::table('states')->insert(['id' => 1, 'uuid' => 'qo-st-01', 'name' => 'DC', 'country_id' => 1]);
    \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 1, 'uuid' => 'qo-ct-01', 'name' => 'Caracas', 'state_id' => 1]);
    \Illuminate\Support\Facades\DB::table('provider_profiles')->insert(['id' => 1, 'uuid' => 'qo-pp-01', 'user_id' => $user->id, 'type' => 'PHARMACY', 'commercial_name' => 'Farmatodo', 'rif' => 'J-12345678-9']);

    // Seed a prescription (required FK for quote_request)
    $rxUuid = 'c0000001-0000-4000-8000-000000000059';
    Prescription::create([
        'uuid'         => $rxUuid,
        'user_id'      => $user->id,
        'patient_id'   => $patient->id,
        'date'         => Carbon::now(),
        'status'       => 'ACTIVE',
        'public_token' => 'tok-qo',
    ]);

    $qrUuid    = 'c0000001-0000-4000-8000-000000000061';
    $offerUuid = 'c0000001-0000-4000-8000-000000000062';

    $payload = [
        'push' => [
            'quote_requests' => [buildQuoteRequestPayload($qrUuid, $patientUuid, ['prescription_uuid' => $rxUuid, 'city_id' => 1])],
            'quote_offers'   => [buildQuoteOfferPayload($offerUuid, $qrUuid)],
        ],
    ];

    $response = $this->actingAs($user, 'user_api')->postJson('/api/v1/sync', $payload);

    $response->assertOk()
        ->assertJsonPath('push_results.quote_requests.success.0', $qrUuid)
        ->assertJsonPath('push_results.quote_offers.success.0', $offerUuid);

    expect(QuoteOffer::where('uuid', $offerUuid)->exists())->toBeTrue();
});
