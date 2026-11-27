<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSiteAccess;
use App\Http\Middleware\ResolveSite;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Behind a TLS-terminating proxy, Laravel must read X-Forwarded-* to
        // generate https URLs and secure cookies. Only enable this when the
        // API is not directly exposed to the internet.
        $trustedProxies = env('TRUSTED_PROXIES');

        if ($trustedProxies) {
            $middleware->trustProxies(
                at: $trustedProxies === '*'
                    ? '*'
                    : array_map('trim', explode(',', $trustedProxies)),
            );
        }

        $trustedHosts = env('TRUSTED_HOSTS');

        if ($trustedHosts) {
            $middleware->trustHosts(
                at: array_map('trim', explode(',', $trustedHosts)),
                subdomains: false,
            );
        }

        $middleware->alias([
            'site' => ResolveSite::class,
            'site.access' => EnsureSiteAccess::class,
            'permission' => EnsurePermission::class,
            'locale' => SetLocale::class,
        ]);

        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The submitted data is invalid.',
                    'errors' => $e->errors(),
                    'code' => 'validation_error',
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The requested resource was not found.',
                    'code' => 'not_found',
                ], 404);
            }
        });
    })->create();
