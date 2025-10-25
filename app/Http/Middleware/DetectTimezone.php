<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if timezone is provided via header or request
        $timezone = $request->header('X-Timezone') ?? $request->get('timezone');

        if ($timezone && $this->isValidTimezone($timezone)) {
            // Store in session
            session(['user_timezone' => $timezone]);

            // Set application timezone for this request
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        } else {
            // Use session timezone if available, otherwise fall back to default
            $sessionTimezone = session('user_timezone');
            if ($sessionTimezone && $this->isValidTimezone($sessionTimezone)) {
                config(['app.timezone' => $sessionTimezone]);
                date_default_timezone_set($sessionTimezone);
            }
        }

        return $next($request);
    }

    /**
     * Check if timezone is valid
     */
    private function isValidTimezone($timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list());
    }
}
