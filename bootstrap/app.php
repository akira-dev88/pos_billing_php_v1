<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Helpers\ResponseHelper;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        // Sanctum SPA support
        $middleware->statefulApi();

        // 🔐 Custom middleware aliases
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role'   => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {

        // ✅ Validation errors
        $exceptions->render(function (ValidationException $e, $request) {
            return ResponseHelper::error(
                collect($e->errors())->flatten()->first(),
                422
            );
        });

        // ✅ HTTP errors (404, 403, etc.)
        $exceptions->render(function (HttpException $e, $request) {
            return ResponseHelper::error(
                $e->getMessage() ?: 'HTTP Error',
                $e->getStatusCode()
            );
        });

        // ✅ Generic / fallback
        $exceptions->render(function (\Throwable $e, $request) {

            return ResponseHelper::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
                500
            );
        });
    })

    ->create();