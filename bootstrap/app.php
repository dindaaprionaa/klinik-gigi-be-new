<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        // Alias middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {

        // Handle Token Expired / Unauthorized
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Token expired or unauthorized'
            ], 401);
        });

    })

    ->create();
