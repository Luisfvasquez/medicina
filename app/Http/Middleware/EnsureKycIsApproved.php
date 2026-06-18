<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKycIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('user_api')->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Si es DOCTOR o PROVIDER, verificamos que tenga al menos un VerificationDocument aprobado.
        // Asumiendo que el enum UserRole se llama DOCTOR y PROVIDER.
        if (in_array($user->role->value, ['DOCTOR', 'PROVIDER'])) {
            $hasApprovedDoc = $user->verificationDocuments()->where('status', 'APPROVED')->exists();
            if (!$hasApprovedDoc) {
                return response()->json([
                    'message' => 'Su documentación se encuentra en revisión. Acceso restringido.'
                ], 403);
            }
        }

        return $next($request);
    }
}
