<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('user_api')->user();

        if ($user && !$user->is_active) {
            // Invalidar token forzadamente
            auth('user_api')->logout();
            return response()->json(['message' => 'Cuenta suspendida.'], 401);
        }

        return $next($request);
    }
}
