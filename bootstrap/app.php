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
            at: ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
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
