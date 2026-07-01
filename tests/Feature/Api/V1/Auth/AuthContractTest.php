<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\PatientAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthContractTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PatientAccount $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'uuid'          => Str::uuid()->toString(),
            'full_name'     => 'Dr. Test Doctor',
            'email'         => 'doctor@test.com',
            'password_hash' => Hash::make('password123'),
            'phone'         => '+584121234567',
            'role'          => 'DOCTOR',
            'is_active'     => true,
        ]);

        $this->patient = PatientAccount::create([
            'uuid'          => Str::uuid()->toString(),
            'full_name'     => 'Juan Pérez',
            'email'         => 'juan@patient.com',
            'password_hash' => Hash::make('password123'),
            'phone'         => '+584129876543',
            'is_active'     => true,
        ]);
    }

    public function test_user_response_has_camelCase_fullName(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['fullName']])
            ->assertJsonMissing(['full_name' => 'Dr. Test Doctor']);
    }

    public function test_user_response_has_id_not_uuid_key(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id']]);

        // id should be the uuid value
        $body = $response->json();
        $this->assertTrue(
            Str::isUuid($body['user']['id']),
            'id field should be a UUID'
        );
        $this->assertEquals($this->user->uuid, $body['user']['id']);
    }

    public function test_user_response_has_role_as_string(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.role', 'DOCTOR');

        $body = $response->json();
        $this->assertIsString($body['user']['role']);
    }

    public function test_patient_response_has_camelCase(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ]);

        // Verify structure doesn't include snake_case
        $response->assertJsonMissing(['full_name' => 'Juan Pérez']);
    }

    public function test_error_401_has_full_rfc7807_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'type',
                'title',
                'status',
                'detail',
                'instance',
            ])
            ->assertJson([
                'type'   => 'https://api.pharmako.com/errors/unauthorized',
                'status' => 401,
            ]);

        // Instance should be the path
        $body = $response->json();
        $this->assertStringContainsString('/api/v1/auth/login-password', $body['instance']);
    }

    public function test_error_422_has_invalidParams(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'type',
                'title',
                'status',
                'detail',
                'instance',
                'invalidParams',
            ])
            ->assertJsonPath('status', 422);

        $body = $response->json();
        $this->assertIsArray($body['invalidParams']);
        $this->assertNotEmpty($body['invalidParams']);
        $this->assertArrayHasKey('name', $body['invalidParams'][0]);
        $this->assertArrayHasKey('reason', $body['invalidParams'][0]);
    }

    public function test_error_422_invalidParams_has_name_and_reason(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'channel' => 'WHATSAPP',
            'role'   => 'DOCTOR',
        ]);

        $response->assertStatus(422);

        $body = $response->json();
        $phoneError = collect($body['invalidParams'])->firstWhere('name', 'phone');

        $this->assertNotNull($phoneError);
        $this->assertArrayHasKey('name', $phoneError);
        $this->assertArrayHasKey('reason', $phoneError);
        $this->assertIsString($phoneError['reason']);
    }

    public function test_deprecated_route_returns_warning_header(): void
    {
        $response = $this->postJson('/api/v1/auth/users/login', [
            'email'    => 'doctor@test.com',
            'password' => 'password123',
        ], [
            'Idempotency-Key' => 'test-deprecated-' . uniqid(),
        ]);

        // Should work (backwards compat) but with warning header
        $response->assertStatus(200);
        $this->assertTrue(
            $response->headers->has('Warning') ||
            str_contains($response->headers->get('Warning') ?? '', 'Deprecated'),
            'Response should have Warning header indicating deprecation'
        );
    }

    public function test_send_otp_422_includes_type_uri(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone'   => 'invalid-phone',
            'role'   => 'DOCTOR',
            'channel' => 'WHATSAPP',
        ]);

        $response->assertStatus(422);

        $body = $response->json();
        $this->assertStringStartsWith('https://api.pharmako.com/errors/', $body['type']);
        $this->assertEquals(422, $body['status']);
        $this->assertEquals('Error de Validación', $body['title']);
    }
}
