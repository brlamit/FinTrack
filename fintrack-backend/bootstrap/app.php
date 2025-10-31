<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'api.validation' => \App\Http\Middleware\ApiValidationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e) {
            // Log all exceptions
            Log::error('Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        $exceptions->render(function (Throwable $e, $request) {
            // API error responses
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'error' => [
                            'code' => 'VALIDATION_ERROR',
                            'message' => 'Validation failed',
                            'details' => $e->errors()
                        ]
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'error' => [
                            'code' => 'AUTHENTICATION_ERROR',
                            'message' => 'Unauthenticated'
                        ]
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'error' => [
                            'code' => 'AUTHORIZATION_ERROR',
                            'message' => 'Unauthorized'
                        ]
                    ], 403);
                }

                // Generic API error
                return response()->json([
                    'error' => [
                        'code' => 'INTERNAL_ERROR',
                        'message' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
                    ]
                ], 500);
            }
        });
    })->create();
