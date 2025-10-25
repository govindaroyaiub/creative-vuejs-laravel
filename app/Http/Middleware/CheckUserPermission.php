<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    protected $alwaysAllow = [
        '/dashboard',
        '/cache-management'
    ];

    public function handle(Request $request, Closure $next)
    {
        $currentRoute = '/' . ltrim($request->path(), '/');

        // ✅ 1. Always allow these public routes
        if (in_array($currentRoute, ['/change-password', '/welcome-to-planetnine/register'])) {
            return $next($request);
        }

        $user = auth()->user();

        // ✅ 2. Ensure user is authenticated
        if (!$user || !$user->permissions) {
            abort(403, 'Sorry mate! You do not have permission to access this page.');
        }

        // ✅ 3. Always allow if in alwaysAllow or cache management routes
        if (in_array($currentRoute, $this->alwaysAllow) || str_starts_with($currentRoute, '/cache-management')) {
            return $next($request);
        }

        // ✅ 4. Allow if user has wildcard *
        if (in_array('*', $user->permissions)) {
            return $next($request);
        }

        // ✅ 5. Allow if any permission is a prefix of the current route
        foreach ($user->permissions as $permission) {
            if (str_starts_with($currentRoute, $permission)) {
                return $next($request);
            }
        }

        // ❌ Denied
        abort(403, 'You do not have permission to access this page.');
    }
}
