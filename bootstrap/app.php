<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\DetectTimezone;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnhancedRateLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // ✅ Include this
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            DetectTimezone::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            // SecurityHeaders::class, // Temporarily disabled
        ]);

        // Enable session for API routes (needed for notification system)
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'enhanced.throttle' => EnhancedRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        // Cache Management - Daily cleanup at configurable time
        $schedule->call(function () {
            $settings = \Illuminate\Support\Facades\DB::table('scheduler_settings')
                ->whereIn('key', ['cache_cleanup_enabled', 'cache_cleanup_time'])
                ->pluck('value', 'key');

            $enabled = ($settings['cache_cleanup_enabled'] ?? 'true') === 'true';
            $time = $settings['cache_cleanup_time'] ?? '04:30';

            if ($enabled) {
                \Illuminate\Support\Facades\Artisan::call('cache:auto-cleanup', ['--type' => 'all']);
            }
        })
            ->name('cache-auto-cleanup')
            ->daily()
            ->withoutOverlapping();
    })
    ->create();
