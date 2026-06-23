<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPatientStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $patient = auth('patient_api')->user();

        if (!$patient) {
            return $next($request);
        }

        // Bloqueo total: is_active = false o status = BANNED
        if (!$patient->is_active || $patient->status->value === 'BANNED') {
            auth('patient_api')->logout();
            return response()->json([
                'message' => 'Cuenta bloqueada.',
                'status' => 'BANNED'
            ], 401);
        }

        // Alerta: status = SUSPENDED o WARNED
        if (in_array($patient->status->value, ['SUSPENDED', 'WARNED'])) {
            return $next($request)->withHeaders([
                'X-Account-Status' => $patient->status->value,
            ]);
        }

        return $next($request);
    }
}