<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Enable Laravel Sanctum SPA authentication
        $middleware->statefulApi();

        $middleware->alias([
            'auth.custom' => \App\Http\Middleware\AuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Do not redirect unauthenticated API requests
        // to a Laravel "login" route.
        $exceptions->shouldRenderJsonWhen(function ($request, $input) {
            return $request->is('api/*') || $request->expectsJson();
        });

    })
    ->create();
