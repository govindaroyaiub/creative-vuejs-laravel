<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Get security headers from config
        $headers = config('security.headers', []);

        // Enhanced Security Headers
        $this->setSecurityHeaders($response, $headers, $request);

        // Remove dangerous headers
        $this->removeDangerousHeaders($response);

        // Log security events for suspicious requests
        $this->logSecurityEvents($request);

        return $response;
    }

    /**
     * Set comprehensive security headers
     */
    private function setSecurityHeaders(Response $response, array $headers, Request $request): void
    {
        // Basic security headers
        $response->headers->set('X-Frame-Options', $headers['x_frame_options'] ?? 'DENY');
        $response->headers->set('X-Content-Type-Options', $headers['x_content_type_options'] ?? 'nosniff');
        $response->headers->set('X-XSS-Protection', $headers['x_xss_protection'] ?? '1; mode=block');

        // HSTS (only for HTTPS)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                $headers['strict_transport_security'] ?? 'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Enhanced Content Security Policy (more permissive for development)
        $csp = $headers['content_security_policy'] ??
            "default-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: blob:; " .
            "style-src 'self' 'unsafe-inline' https: data:; " .
            "img-src 'self' 'unsafe-inline' data: https: blob:; " .
            "font-src 'self' 'unsafe-inline' data: https:; " .
            "connect-src 'self' https: ws: wss:; " .
            "media-src 'self' https: data: blob:; " .
            "object-src 'self'; " .
            "base-uri 'self'; " .
            "form-action 'self'";

        $response->headers->set('Content-Security-Policy', $csp);

        // Referrer Policy
        $response->headers->set(
            'Referrer-Policy',
            $headers['referrer_policy'] ?? 'strict-origin-when-cross-origin'
        );

        // Enhanced Permissions Policy
        $permissionsPolicy = $headers['permissions_policy'] ??
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), ' .
            'accelerometer=(), gyroscope=(), magnetometer=(), ' .
            'clipboard-read=(), clipboard-write=()';

        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // Additional security headers (relaxed for development)
        // $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        // $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Prevent MIME type sniffing
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Cache control for sensitive pages
        if ($this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }

    /**
     * Remove potentially dangerous headers
     */
    private function removeDangerousHeaders(Response $response): void
    {
        $dangerousHeaders = [
            'X-Powered-By',
            'Server',
            'X-AspNet-Version',
            'X-AspNetMvc-Version',
            'X-Generator',
            'X-Drupal-Cache',
            'X-Varnish',
        ];

        foreach ($dangerousHeaders as $header) {
            $response->headers->remove($header);
        }
    }

    /**
     * Check if the current page contains sensitive information
     */
    private function isSensitivePage(Request $request): bool
    {
        $sensitiveRoutes = [
            'login',
            'register',
            'password',
            'admin',
            'dashboard',
            'user-management',
            'bills',
            'clients',
            'settings'
        ];

        $path = $request->path();

        foreach ($sensitiveRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log security events for monitoring
     */
    private function logSecurityEvents(Request $request): void
    {
        // Log suspicious requests
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
            ]);
        }
    }

    /**
     * Detect suspicious requests
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        $path = strtolower($request->path());

        // Common attack patterns
        $suspiciousPatterns = [
            'sqlmap',
            'nikto',
            'nessus',
            'acunetix',
            'burp',
            'owasp',
            '../',
            '<script',
            'union select',
            'drop table',
            'exec(',
            'eval(',
        ];

        // Check user agent and path for suspicious patterns
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgent, $pattern) || str_contains($path, $pattern)) {
                return true;
            }
        }

        // Check for excessive requests from same IP (basic rate limiting detection)
        $clientIp = $request->ip();
        if ($this->isExcessiveRequests($clientIp)) {
            return true;
        }

        return false;
    }

    /**
     * Simple check for excessive requests (enhance with Redis/Cache for production)
     */
    private function isExcessiveRequests(string $ip): bool
    {
        // This is a simple implementation - use Redis/Cache for production
        $cacheKey = "security_requests_{$ip}";
        $requests = cache()->get($cacheKey, 0);

        if ($requests > 100) { // More than 100 requests per minute
            return true;
        }

        cache()->put($cacheKey, $requests + 1, 60); // 60 seconds
        return false;
    }
}
