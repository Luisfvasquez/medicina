<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a Deprecation Warning header to responses from deprecated routes.
 */
class DeprecatedRoute
{
    public function handle(Request $request, Closure $next, string $suggestion): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Warning',
            '299 - "Deprecated: ' . $suggestion . '"'
        );

        return $response;
    }
}
