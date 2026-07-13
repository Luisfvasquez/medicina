<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $request->header('X-Timezone') ?? $request->input('timezone');

        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            date_default_timezone_set($timezone);
            config(['app.timezone' => $timezone]);
        } else {
            // Default fallback timezone for doctor/patient region
            date_default_timezone_set('America/Caracas');
            config(['app.timezone' => 'America/Caracas']);
        }

        return $next($request);
    }
}
