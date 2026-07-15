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
    }
}
