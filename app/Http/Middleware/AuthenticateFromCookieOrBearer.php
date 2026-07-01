<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Reads the JWT token from the auth_token cookie (preferred)
 * or from the Authorization: Bearer header, and sets it so that
 * Laravel's auth() helpers work.
 */
class AuthenticateFromCookieOrBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = null;

        // 1. Try cookie first (preferred — mobile/web clients store JWT here)
        if ($request->hasCookie('auth_token')) {
            $token = $request->cookie('auth_token');
        }

        // 2. Fall back to Authorization header (backwards compat, API consumers)
        if (!$token && $request->bearerToken()) {
            $token = $request->bearerToken();
        }

        if ($token) {
            // Manually set the token so auth('user_api') works downstream
            JWTAuth::setToken($token);
        }

        return $next($request);
    }
}
