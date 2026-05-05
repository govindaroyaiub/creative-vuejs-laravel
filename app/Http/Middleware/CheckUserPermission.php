<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    protected $alwaysAllow = [
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

        // ✅ 2. Ensure user is authenticated and has permissions
        if (!$user) {
            return redirect()->route('login')->with('error', 'Authentication required to access this page.');
        }

        if (!$user->permissions || empty($user->permissions)) {
            abort(403, 'You do not have any permissions assigned to your account.');
        }

        // ✅ 3. Check for dashboard and home route permissions
        if ($currentRoute === '/dashboard' || $currentRoute === '/') {
            // Check if user has specific permission for dashboard/home routes
            $hasDashboardPermission = in_array('/', $user->permissions) ||
                in_array('/dashboard', $user->permissions) ||
                in_array('*', $user->permissions);

            if (!$hasDashboardPermission) {
                abort(403, 'You do not have permission to access the dashboard.');
            }
        }

        // ✅ 4. Always allow if in alwaysAllow or cache management routes
        if (in_array($currentRoute, $this->alwaysAllow) || str_starts_with($currentRoute, '/cache-management')) {
            return $next($request);
        }

        // ✅ 5. Allow if user has wildcard *
        if (in_array('*', $user->permissions)) {
            return $next($request);
        }

        // ✅ 6. Allow if any permission matches the current route
        //    EXACT match  ('/previews' matches '/previews')   OR
        //    SEGMENT-BOUNDED prefix ('/previews' matches '/previews/edit/1')
        // The previous version used `str_starts_with` alone, which
        // over-granted: a user with `/previews` was implicitly granted
        // `/previews-delete/{id}`, `/previews-edit/{id}`, etc., because
        // they share the same string prefix. Anchoring the prefix to a
        // `/` boundary closes that.
        foreach ($user->permissions as $permission) {
            if ($currentRoute === $permission) {
                return $next($request);
            }
            if (str_starts_with($currentRoute, rtrim($permission, '/') . '/')) {
                return $next($request);
            }
        }

        // ❌ Denied - show 403 forbidden page with permission message
        abort(403, 'You do not have permission to access this page.');
    }
}
