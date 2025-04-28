<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    protected $alwaysAllow = [
        '/dashboard'
    ];

    public function handle(Request $request, Closure $next)
    {
        $currentRoute = '/' . ltrim($request->path(), '/');

        // ✅ 1. Always allow public routes first
        if (in_array($currentRoute, ['/change-password', '/welcome-to-planetnine/register'])) {
            return $next($request);
        }

        $user = auth()->user();

        // ✅ 2. Now check if user exists for private routes
        if (!$user || !$user->permissions) {
            abort(403, 'Sorry mate! You do not have permission to access this page.');
        }

        // ✅ Always allow safe routes
        if (in_array($currentRoute, $this->alwaysAllow)) {
            return $next($request);
        }

        // ✅ Allow all if wildcard
        if (in_array('*', $user->permissions)) {
            return $next($request);
        }

        // ✅ Allow if specific permission
        if (in_array($currentRoute, $user->permissions)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
