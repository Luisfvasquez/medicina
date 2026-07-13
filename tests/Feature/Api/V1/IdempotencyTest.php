<?php

use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\User;
use App\Models\FollowUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTestUser(): User
{
    return User::create([
        'full_name'     => 'Dr. Idempotency Test',
        'email'         => 'idempdoctor@test.com',
        'password_hash' => bcrypt('password'),
        'role'          => 'DOCTOR',
        'is_active'     => true,
        'phone'         => '+580009876541',
    ]);
}

function createTestPatient(User $user): Patient
{
    $account = PatientAccount::create([
        'email'         => 'idemppatient@patient.test',
        'password_hash' => bcrypt('password'),
        'full_name'     => 'Idemp Patient',
        'phone'         => '+580001111112',
    ]);

    return Patient::create([
        'uuid'               => Str::uuid()->toString(),
        'user_id'            => $user->id,
        'patient_account_id' => $account->id,
        'first_name'         => 'Idemp',
        'last_name'          => 'Patient',
        'birth_date'         => '1995-01-01',
    ]);
}

test('post requests without idempotency key fail with 400', function () {
    $user = createTestUser();
    $patient = createTestPatient($user);

    $response = $this->actingAs($user, 'user_api')
        ->postJson('/api/v1/follow-ups', [
            'uuid' => Str::uuid()->toString(),
            'patient_uuid' => $patient->uuid,
            'scheduled_date' => '2026-08-01',
            'channel' => 'WHATSAPP',
            'message_template' => 'Hola',
        ]);

    $response->assertStatus(400)
        ->assertJsonPath('detail', 'El header Idempotency-Key es requerido para peticiones POST, PUT y PATCH.');
});

test('idempotency key prevents duplicate resource creation', function () {
    $user = createTestUser();
    $patient = createTestPatient($user);
    $idempotencyKey = Str::uuid()->toString();

    $payload = [
        'uuid' => Str::uuid()->toString(),
        'patient_uuid' => $patient->uuid,
        'scheduled_date' => '2026-08-01',
        'channel' => 'WHATSAPP',
        'message_template' => 'Hola',
    ];

    // First request - should succeed and create the follow-up
    $response1 = $this->actingAs($user, 'user_api')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson('/api/v1/follow-ups', $payload);

    $response1->assertStatus(201);
    $firstFollowUpId = $response1->json('data.id');

    // Confirm it exists in database
    expect(FollowUp::count())->toBe(1);

    // Second request - with same idempotency key - should return the cached 201 response
    $response2 = $this->actingAs($user, 'user_api')
        ->withHeaders(['Idempotency-Key' => $idempotencyKey])
        ->postJson('/api/v1/follow-ups', $payload);

    $response2->assertStatus(201);
    expect($response2->json('data.id'))->toBe($firstFollowUpId);

    // Assert that NO second entry was created in the database
    expect(FollowUp::count())->toBe(1);

    // Third request - with DIFFERENT idempotency key - should create a second follow-up
    $payload2 = $payload;
    $payload2['uuid'] = Str::uuid()->toString();
    
    $response3 = $this->actingAs($user, 'user_api')
        ->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson('/api/v1/follow-ups', $payload2);

    $response3->assertStatus(201);
    expect(FollowUp::count())->toBe(2);
});
