<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\AutoLoginAsOwner;
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
        'APP_MAINTENANCE_DRIVER' => 'file',
        'LOG_CHANNEL' => 'stderr',
        'SESSION_DRIVER' => 'database',
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
        'BCRYPT_ROUNDS' => '12',
    ] as $key => $fallback) {
        // NB: empty-string env values are treated as missing. The platform
        // may inject declared-but-unset variables as '', and Laravel's
        // env() helper returns '' verbatim instead of the default, which
        // breaks every driver name resolved from the environment.
        $current = $_SERVER[$key] ?? getenv($key);
        if ($current === false || $current === '') {
            $_ENV[$key] = $_SERVER[$key] = $fallback;
            putenv("{$key}={$fallback}");
        }
    }

    // BCRYPT_ROUNDS must be a valid cost (4-31). Anything else makes
    // password_needs_rehash() always true and password_hash() throw,
    // which breaks login via rehash-on-login. Normalize hard.
    $rounds = $_SERVER['BCRYPT_ROUNDS'] ?? getenv('BCRYPT_ROUNDS');
    if (! is_numeric($rounds) || (int) $rounds < 4 || (int) $rounds > 31) {
        $_ENV['BCRYPT_ROUNDS'] = $_SERVER['BCRYPT_ROUNDS'] = '12';
        putenv('BCRYPT_ROUNDS=12');
    }

    // Neon routing with old libpq (no SNI, as bundled in serverless PHP
    // runtimes): the proxy rejects connections with "Endpoint ID is not
    // specified". Workaround D from Neon's docs: pass the endpoint ID
    // (first label of DB_HOST) inside the password field. Done here so the
    // dashboard value stays a plain password. See
    // https://neon.com/docs/connect/connection-errors
    $dbPassword = $_SERVER['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
    $dbHost = $_SERVER['DB_HOST'] ?? getenv('DB_HOST');
    if (is_string($dbPassword) && $dbPassword !== '' && ! str_starts_with($dbPassword, 'endpoint=')
        && is_string($dbHost) && str_contains($dbHost, '.')
    ) {
        $endpointId = explode('.', $dbHost)[0];
        $suffixed = "endpoint={$endpointId}\${$dbPassword}";
        $_ENV['DB_PASSWORD'] = $_SERVER['DB_PASSWORD'] = $suffixed;
        putenv("DB_PASSWORD={$suffixed}");
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

        // Stateless auto-login: must run BEFORE Authenticate, and uses
        // Auth::setUser (no session write) so serverless cookie issues
        // can't cause a login <-> dashboard redirect loop.
        $middleware->web(prepend: [
            AutoLoginAsOwner::class,
        ]);

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
