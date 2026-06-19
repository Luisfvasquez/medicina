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
                'error' => 'Idempotency-Key header is missing and it is strictly required.'
            ], 400);
        }

        // Para evitar colisiones entre distintos endpoints con la misma llave accidental
        $cacheKey = 'idempotency_' . md5($request->url()) . '_' . $idempotencyKey;

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
