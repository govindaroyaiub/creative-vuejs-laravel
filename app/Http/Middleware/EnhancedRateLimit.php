<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnhancedRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$parameters): SymfonyResponse
    {
        // Parse parameters: maxAttempts|decayMinutes|keyGenerator
        $maxAttempts = $parameters[0] ?? 60;
        $decayMinutes = $parameters[1] ?? 1;
        $keyGenerator = $parameters[2] ?? 'ip';

        $key = $this->resolveRequestSignature($request, $keyGenerator);

        // Check if user is temporarily banned
        if ($this->isTemporarilyBanned($key)) {
            Log::warning('Rate limit: Temporarily banned IP attempted access', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Too many requests. You have been temporarily banned.',
                'retry_after' => $this->getRetryAfter($key)
            ], 429);
        }

        // Apply rate limiting
        $response = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () use ($next, $request) {
                return $next($request);
            },
            $decayMinutes * 60
        );

        if ($response === false) {
            $this->handleRateLimitExceeded($request, $key, $maxAttempts, $decayMinutes);

            return response()->json([
                'message' => 'Too many requests.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        return $this->addRateLimitHeaders($response, $key, $maxAttempts);
    }

    /**
     * Resolve the rate limiting key for the request
     */
    protected function resolveRequestSignature(Request $request, string $keyGenerator): string
    {
        switch ($keyGenerator) {
            case 'user':
                return 'rate_limit:user:' . ($request->user()?->id ?? $request->ip());
            case 'route':
                return 'rate_limit:route:' . $request->route()?->getName() . ':' . $request->ip();
            case 'global':
                return 'rate_limit:global:' . $request->ip();
            default:
                return 'rate_limit:ip:' . $request->ip();
        }
    }

    /**
     * Check if the IP is temporarily banned
     */
    protected function isTemporarilyBanned(string $key): bool
    {
        $banKey = "ban:{$key}";
        return Cache::has($banKey);
    }

    /**
     * Get retry after seconds for banned IP
     */
    protected function getRetryAfter(string $key): int
    {
        $banKey = "ban:{$key}";
        return Cache::get($banKey, 0);
    }

    /**
     * Handle rate limit exceeded - implement progressive penalties
     */
    protected function handleRateLimitExceeded(Request $request, string $key, int $maxAttempts, int $decayMinutes): void
    {
        $violationKey = "violations:{$key}";
        $violations = Cache::get($violationKey, 0) + 1;

        // Progressive ban durations
        $banDuration = match (true) {
            $violations >= 10 => 3600, // 1 hour for 10+ violations
            $violations >= 5 => 1800,  // 30 minutes for 5+ violations
            $violations >= 3 => 600,   // 10 minutes for 3+ violations
            default => 300             // 5 minutes for first violations
        };

        // Store violation count
        Cache::put($violationKey, $violations, 86400); // 24 hours

        // Apply temporary ban
        $banKey = "ban:{$key}";
        Cache::put($banKey, $banDuration, $banDuration);

        Log::warning('Rate limit exceeded - temporary ban applied', [
            'ip' => $request->ip(),
            'path' => $request->path(),
            'violations' => $violations,
            'ban_duration' => $banDuration,
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, string $key, int $maxAttempts)
    {
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $retryAfter = RateLimiter::availableIn($key);

        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->add([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => max(0, $remaining),
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);

            if ($remaining === 0) {
                $response->headers->set('Retry-After', $retryAfter);
            }
        }

        return $response;
    }
}
