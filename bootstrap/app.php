<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Enable Laravel Sanctum SPA authentication
        $middleware->statefulApi();

        // Register custom authorization middleware
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Prevent Laravel from redirecting API guests
        // to a named login route.
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return null;
            }

            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Always return JSON for API requests
        $exceptions->shouldRenderJsonWhen(function ($request, $input) {
            return $request->is('api/*') ||
                $request->expectsJson();
        });
    })
    ->create();
