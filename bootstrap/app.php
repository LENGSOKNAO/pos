<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

// Vercel serverless: read-only filesystem (except /tmp) and vercel.json
// env is not always injected into functions, so enforce safe fallbacks here.
// Real environment variables (dashboard) always win when present.
if (($_SERVER['VERCEL'] ?? getenv('VERCEL')) === '1') {
    foreach ([
        'APP_ENV' => 'production',
        'LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'cookie',
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'VIEW_COMPILED_PATH' => '/tmp',
        'APP_CONFIG_CACHE' => '/tmp/config.php',
        'APP_EVENTS_CACHE' => '/tmp/events.php',
        'APP_PACKAGES_CACHE' => '/tmp/packages.php',
        'APP_ROUTES_CACHE' => '/tmp/routes.php',
        'APP_SERVICES_CACHE' => '/tmp/services.php',
        'DB_CONNECTION' => 'pgsql',
        'DB_SSLMODE' => 'require',
    ] as $key => $fallback) {
        if (! isset($_SERVER[$key]) && getenv($key) === false) {
            $_ENV[$key] = $_SERVER[$key] = $fallback;
            putenv("{$key}={$fallback}");
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Vercel's edge (TLS terminated at the proxy).
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'permission' => CheckPermission::class,
            'role' => CheckRole::class,
            'audit' => AuditLogMiddleware::class,
        ]);

        $middleware->web(replace: [
            PreventRequestForgery::class => VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
