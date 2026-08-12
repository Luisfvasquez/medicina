<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicamos la idempotencia a peticiones POST, PUT o PATCH
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/validation',
                'title'    => 'Error de Validación',
                'status'   => 400,
                'detail'   => 'El header Idempotency-Key es requerido para peticiones POST, PUT y PATCH.',
                'instance' => '/' . $request->path(),
                'invalidParams' => [
                    ['name' => 'Idempotency-Key', 'reason' => 'El header es requerido.'],
                ],
            ], 400);
        }

        // Para evitar colisiones entre distintos endpoints y usuarios con la misma llave accidental
        $userId = $request->user()?->id ?? 'anonymous';
        $cacheKey = 'idempotency_' . $userId . '_' . md5($request->url()) . '_' . $idempotencyKey;

        // Utilizamos un Lock atómico para prevenir condiciones de carrera (Check-then-act)
        $lock = Cache::lock($cacheKey . '_lock', 30);

        try {
            // Esperamos hasta 5 segundos para adquirir el lock, por si hay otro request procesándose
            if (!$lock->block(5)) {
                return response()->json([
                    'type'     => 'https://api.pharmako.com/errors/conflict',
                    'title'    => 'Conflicto de Concurrencia',
                    'status'   => 409,
                    'detail'   => 'Ya hay una petición en progreso con esta misma llave de idempotencia.',
                    'instance' => '/' . $request->path(),
                ], 409);
            }

            // Una vez adquirido el lock, verificamos si la respuesta ya está en caché
            if (Cache::has($cacheKey)) {
                $cachedResponse = Cache::get($cacheKey);
                return response($cachedResponse['content'], $cachedResponse['status'], $cachedResponse['headers']);
            }

            $response = $next($request);

            // Guardar la respuesta en caché si fue exitosa (2xx)
            if ($response->isSuccessful()) {
                Cache::put($cacheKey, [
                    'content' => $response->getContent(),
                    'status'  => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                ], now()->addHours(24));
            }

            return $response;
            
        } finally {
            // Siempre liberamos el lock, ya sea que haya fallado o sido exitoso
            $lock?->release();
        }
    }
}
