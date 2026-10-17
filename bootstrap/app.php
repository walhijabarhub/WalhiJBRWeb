<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            // Resolved lazily: configuration is not loaded yet when this callback runs.
            guests: fn () => '/'.config('auth.login_path', 'portal-jabar'),
            users: '/admin',
        );

        $middleware->trustProxies(
            // nginx is the edge server and passes the client address as REMOTE_ADDR (with
            // real_ip for Cloudflare), so no proxy is trusted by default. Set TRUSTED_PROXIES
            // only when another reverse proxy sits in front. Read with env(): configuration
            // is not loaded yet when this callback runs (container env vars are always set).
            at: array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->trustHosts(
            at: function () {
                $hosts = [
                    '^(.+\.)?walhi\-jabar\.org$',
                    '^(.+\.)?walhijabar\.co\.id$',
                    '^(.+\.)?walhijabar\.or\.id$',
                    '^(.+\.)?walhijabar\.org$',
                    '^localhost$',
                    '^127\.0\.0\.1$',
                    '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(:[0-9]+)?$',
                ];

                $appHost = parse_url(config('app.url'), PHP_URL_HOST);
                if ($appHost && ! in_array($appHost, ['localhost', '127.0.0.1'])) {
                    $escapedHost = preg_quote($appHost, '/');
                    $hosts[] = '^(.+\.)?'.$escapedHost.'$';
                    $hosts[] = '^'.$escapedHost.'$';
                }

                return array_values(array_unique($hosts));
            },
        );

        $middleware->validateCsrfTokens(except: [
            'donasi/webhook',
        ]);

        $middleware->alias([
            'role' => CheckRole::class,
            'two-factor' => EnsureTwoFactorEnabled::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (QueryException $e, Request $request) {
            if (! config('app.debug')) {
                Log::error('Database Query Exception', [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);

                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'message' => 'Internal Server Error: A database operation failed.',
                    ], 500);
                }

                abort(500, 'A database error occurred.');
            }
        });

        $exceptions->render(function (PDOException $e, Request $request) {
            if (! config('app.debug')) {
                Log::error('PDO Exception: '.$e->getMessage());

                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'message' => 'Internal Server Error: A database connection error occurred.',
                    ], 500);
                }

                abort(500, 'A database connection error occurred.');
            }
        });
    })->create();
