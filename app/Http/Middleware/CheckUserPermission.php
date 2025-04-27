<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    protected $alwaysAllow = [
        '/dashboard',
        '/user-managements/users',
        '/user-managements/designations',
        '/user-managements/routes',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !$user->permissions) {
            abort(403, 'Sorry mate! You do not have permission to access this page.');
        }

        $currentRoute = '/' . ltrim($request->path(), '/');

        // ✅ Always allow safe routes
        if (in_array($currentRoute, $this->alwaysAllow)) {
            return $next($request);
        }

        // ✅ Allow all if wildcard
        if (in_array('*', $user->permissions)) {
            return $next($request);
        }

        // ✅ Allow if route exists in permission
        if (in_array($currentRoute, $user->permissions)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}