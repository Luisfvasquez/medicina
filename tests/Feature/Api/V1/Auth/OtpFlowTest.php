<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\OtpAttempt;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OtpFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'uuid'          => Str::uuid()->toString(),
            'full_name'     => 'Dr. Test User',
            'email'         => 'doctor@test.com',
            'password_hash' => Hash::make('password123'),
            'phone'         => '+584121234567',
            'role'          => 'DOCTOR',
            'is_active'     => true,
        ]);
    }

    // ─── send-otp ─────────────────────────────────────────────────────────────

    public function test_send_otp_whatsapp_returns_200(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone'   => '+584121234567',
            'role'   => 'DOCTOR',
            'channel' => 'WHATSAPP',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Código de verificación enviado con éxito.',
            ])
            ->assertJsonStructure(['otpExpirySeconds']);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => '+584121234567',
            'channel'   => 'WHATSAPP',
            'role'      => 'DOCTOR',
        ]);
    }

    public function test_send_otp_email_returns_200(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'email'   => 'doctor@test.com',
            'role'   => 'DOCTOR',
            'channel' => 'EMAIL',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Código de verificación enviado con éxito.',
            ]);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => 'doctor@test.com',
            'channel'   => 'EMAIL',
            'role'      => 'DOCTOR',
        ]);
    }

    public function test_send_otp_whatsapp_requires_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'role'   => 'DOCTOR',
            'channel' => 'WHATSAPP',
        ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 422])
            ->assertJsonPath('invalidParams.0.name', 'phone');
    }

    public function test_send_otp_email_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'role'   => 'DOCTOR',
            'channel' => 'EMAIL',
        ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 422])
            ->assertJsonPath('invalidParams.0.name', 'email');
    }

    public function test_send_otp_rate_limit_blocks_after_max_attempts(): void
    {
        // Simulate 3 failed attempts
        OtpAttempt::create([
            'identifier'  => '+584121234567',
            'role'        => 'DOCTOR',
            'attempts'    => 3,
            'locked_until' => now()->addMinutes(30),
        ]);

        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone'   => '+584121234567',
            'role'   => 'DOCTOR',
            'channel' => 'WHATSAPP',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'type'   => 'https://api.pharmako.com/errors/rate-limited',
                'title'  => 'Demasiados Intentos',
                'status' => 429,
            ]);
    }

    // ─── verify-otp ───────────────────────────────────────────────────────────

    public function test_verify_otp_correct_code_returns_200_with_cookie(): void
    {
        $length = (int) config('otp.code_length', 8);
        $plainCode = str_repeat('1', $length);
        $codeHash  = hash('sha256', $plainCode);

        OtpCode::create([
            'uuid'       => Str::uuid()->toString(),
            'identifier' => '+584121234567',
            'channel'    => 'WHATSAPP',
            'role'       => 'DOCTOR',
            'code_hash'  => $codeHash,
            'expires_at' => now()->addSeconds(180),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+584121234567',
            'code'  => $plainCode,
            'role'  => 'DOCTOR',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'fullName', 'email', 'phone', 'role']])
            ->assertJsonPath('user.email', 'doctor@test.com')
            ->assertJsonPath('user.role', 'DOCTOR');

        $response->assertCookie('auth_token');
    }

    public function test_verify_otp_incorrect_code_returns_401(): void
    {
        $length = (int) config('otp.code_length', 8);
        $plainCode = str_repeat('1', $length);
        $wrongCode = str_repeat('9', $length);
        $codeHash  = hash('sha256', $plainCode);

        OtpCode::create([
            'uuid'       => Str::uuid()->toString(),
            'identifier' => '+584121234567',
            'channel'    => 'WHATSAPP',
            'role'       => 'DOCTOR',
            'code_hash'  => $codeHash,
            'expires_at' => now()->addSeconds(180),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+584121234567',
            'code'  => $wrongCode,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'type'   => 'https://api.pharmako.com/errors/otp-invalid',
                'title'  => 'Código Inválido',
                'status' => 401,
            ]);
    }

    public function test_verify_otp_expired_code_returns_401(): void
    {
        $length = (int) config('otp.code_length', 8);
        $plainCode = str_repeat('1', $length);
        $codeHash  = hash('sha256', $plainCode);

        OtpCode::create([
            'uuid'       => Str::uuid()->toString(),
            'identifier' => '+584121234567',
            'channel'    => 'WHATSAPP',
            'role'       => 'DOCTOR',
            'code_hash'  => $codeHash,
            'expires_at' => now()->subSecond(),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+584121234567',
            'code'  => $plainCode,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'type'  => 'https://api.pharmako.com/errors/otp-expired',
                'title' => 'Código Vencido',
            ]);
    }

    public function test_verify_otp_no_code_returns_404(): void
    {
        $length = (int) config('otp.code_length', 8);
        $plainCode = str_repeat('1', $length);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+584121234567',
            'code'  => $plainCode,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'type'  => 'https://api.pharmako.com/errors/not-found',
                'title' => 'Código No Encontrado',
            ]);
    }

    public function test_verify_otp_includes_auth_cookie(): void
    {
        $length = (int) config('otp.code_length', 8);
        $plainCode = str_repeat('1', $length);
        $codeHash  = hash('sha256', $plainCode);

        OtpCode::create([
            'uuid'       => Str::uuid()->toString(),
            'identifier' => '+584121234567',
            'channel'    => 'WHATSAPP',
            'role'       => 'DOCTOR',
            'code_hash'  => $codeHash,
            'expires_at' => now()->addSeconds(180),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+584121234567',
            'code'  => $plainCode,
            'role'  => 'DOCTOR',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('Set-Cookie'));
    }
}
