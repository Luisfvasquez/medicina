<?php

namespace App\Guards;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Custom JWT guard that reads the token from the auth_token cookie
 * (preferred) or the Authorization: Bearer header (fallback).
 *
 * Register in config/auth.php:
 *   'user_api' => ['driver' => 'cookie_jwt', 'provider' => 'users'],
 */
class CookieJwtGuard implements \Illuminate\Contracts\Auth\Guard
{
    protected ?string $token = null;
    protected ?Authenticatable $user = null;

    /**
     * Determine if the guard has a user instance.
     */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function __construct(
        protected JWT $jwt,
        protected UserProvider $provider,
        protected Request $request,
    ) {}

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if (!$this->token) {
            $this->parseToken();
        }

        if (!$this->token) {
            return null;
        }

        try {
            $payload = $this->jwt->setToken($this->token)->getPayload();
            $id = $payload->get('sub');

            if (!$id) {
                return null;
            }

            $this->user = $this->provider->retrieveById($id);

            return $this->user;
        } catch (JWTException) {
            return null;
        }
    }

    /**
     * Check if the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Check if the current user is a guest.
     */
    public function guest(): bool
    {
        // Guest = has a user but is not logged in.
        // If we have no token at all, we want the auth middleware to return 401,
        // so we return false (not a guest = unauthenticated, handled elsewhere).
        return $this->token !== null && $this->user === null;
    }

    /**
     * Get the ID of the current user.
     */
    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Determine if the current user is authenticated via "remember me".
     */
    public function viaRemember(): bool
    {
        return false;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $user !== null
            && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate using credentials, returns JWT token.
     */
    public function attempt(array $credentials = [], bool $login = true): ?string
    {
        /** @var User|Authenticatable|null $user */
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            if ($login) {
                $this->setUser($user);
            }

            return $this->jwt->fromUser($user);
        }

        return null;
    }

    /**
     * Create a JWT from a user.
     *
     * @param User|Authenticatable $user
     */
    public function login(Authenticatable $user): string
    {
        $this->setUser($user);

        return $this->jwt->fromUser($user);
    }

    /**
     * Logout and invalidate the token.
     */
    public function logout(): void
    {
        if ($this->token) {
            try {
                $this->jwt->invalidate($this->token);
            } catch (JWTException) {
                // Ignore — token might already be invalid
            }
        }

        $this->token = null;
        $this->user = null;
    }

    /**
     * Refresh the current token.
     */
    public function refresh(): string
    {
        $this->token = $this->jwt->setToken($this->token)->refresh();

        return $this->token;
    }

    /**
     * Set the current user.
     */
    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the raw JWT token string.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Set the raw JWT token string.
     */
    public function setToken(string $token): static
    {
        $this->token = $token;
        $this->user = null;

        return $this;
    }

    /**
     * Unset the current token.
     */
    public function unsetToken(): void
    {
        $this->token = null;
        $this->user = null;
    }

    /**
     * Parse the token from request — cookie first, then Bearer header.
     */
    protected function parseToken(): void
    {
        $this->token = null;

        // 1. Prefer auth_token cookie (set by login responses)
        if ($this->request->hasCookie('auth_token')) {
            $this->token = $this->request->cookie('auth_token');
        }

        // 2. Fall back to Authorization: Bearer header (API consumers)
        if (!$this->token && $this->request->bearerToken()) {
            $this->token = $this->request->bearerToken();
        }
    }
}
