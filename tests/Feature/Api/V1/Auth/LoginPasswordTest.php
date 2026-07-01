<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'uuid'          => Str::uuid()->toString(),
            'full_name'     => 'Dr. Carlos San José',
            'email'         => 'doctor@pharmako.com',
            'password_hash' => Hash::make('miSuperClave123!'),
            'phone'         => '+584121234567',
            'role'          => 'DOCTOR',
            'is_active'     => true,
        ]);
    }

    public function test_login_password_correct_credentials_returns_200_with_cookie(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@pharmako.com',
            'password' => 'miSuperClave123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'fullName', 'email', 'phone', 'role'],
            ])
            ->assertJsonPath('user.email', 'doctor@pharmako.com')
            ->assertJsonPath('user.fullName', 'Dr. Carlos San José')
            ->assertJsonPath('user.phone', '+584121234567')
            ->assertJsonPath('user.role', 'DOCTOR');

        $response->assertCookie('auth_token');
    }

    public function test_login_password_incorrect_password_returns_401_rfc7807(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@pharmako.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'type'   => 'https://api.pharmako.com/errors/unauthorized',
                'title'  => 'Credenciales Incorrectas',
                'status' => 401,
                'detail' => 'El correo o la contraseña son incorrectos.',
                'instance' => '/api/v1/auth/login-password',
            ]);
    }

    public function test_login_password_user_not_found_returns_401_rfc7807(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'notfound@pharmako.com',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'type'  => 'https://api.pharmako.com/errors/unauthorized',
                'title' => 'Credenciales Incorrectas',
                'status' => 401,
            ]);
    }

    public function test_login_password_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login-password', [
            'password' => 'miSuperClave123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('invalidParams.0.name', 'email');
    }

    public function test_logout_returns_200_and_clears_cookie(): void
    {
        // Login first
        $loginResponse = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@pharmako.com',
            'password' => 'miSuperClave123!',
        ]);

        $token = $loginResponse->headers->get('Set-Cookie');

        // Logout
        $response = $this->withCookie('auth_token', $this->extractToken($token))
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertStringContainsString('auth_token=', $response->headers->get('Set-Cookie'));
        $this->assertStringContainsString('expires=', $response->headers->get('Set-Cookie'));
    }

    public function test_me_returns_current_user(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login-password', [
            'email'    => 'doctor@pharmako.com',
            'password' => 'miSuperClave123!',
        ]);

        $token = $this->extractToken($loginResponse->headers->get('Set-Cookie'));

        $response = $this->withCookie('auth_token', $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'doctor@pharmako.com')
            ->assertJsonPath('user.fullName', 'Dr. Carlos San José')
            ->assertJsonPath('user.role', 'DOCTOR');
    }

    public function test_me_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'type'  => 'https://api.pharmako.com/errors/unauthorized',
                'title' => 'No Autenticado',
                'status' => 401,
            ]);
    }

    private function extractToken(?string $cookieHeader): ?string
    {
        if (!$cookieHeader) {
            return null;
        }

        preg_match('/auth_token=([^;]+)/', $cookieHeader, $matches);

        return $matches[1] ?? null;
    }
}
