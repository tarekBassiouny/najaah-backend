<?php

declare(strict_types=1);

use App\Http\Middleware\JwtAdminMiddleware;
use App\Http\Middleware\JwtMobileMiddleware;
use App\Http\Middleware\SetRequestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Mobile API (JWT)
            Route::prefix('api/v1')
                ->middleware(['api'])
                ->group(function (): void {
                    require __DIR__.'/../routes/api/v1/auth.php';

                    Route::middleware(['jwt.mobile'])->group(function (): void {
                        require __DIR__.'/../routes/api/v1/enrollments.php';
                        require __DIR__.'/../routes/api/v1/courses.php';
                        require __DIR__.'/../routes/api/v1/sections.php';
                        require __DIR__.'/../routes/api/v1/videos.php';
                        require __DIR__.'/../routes/api/v1/pdfs.php';
                        require __DIR__.'/../routes/api/v1/playback.php';
                        require __DIR__.'/../routes/api/v1/extra-view-requests.php';
                        require __DIR__.'/../routes/api/v1/device-change-requests.php';
                    });
                });

            // Admin (JWT)
            Route::prefix('admin')
                ->middleware(['api'])
                ->group(function (): void {
                    require __DIR__.'/../routes/admin/auth.php';

                    Route::middleware(['jwt.admin'])->group(function (): void {
                        require __DIR__.'/../routes/admin/enrollments.php';
                        require __DIR__.'/../routes/admin/courses.php';
                        require __DIR__.'/../routes/admin/sections.php';
                        require __DIR__.'/../routes/admin/videos.php';
                        require __DIR__.'/../routes/admin/pdfs.php';
                        require __DIR__.'/../routes/admin/center-settings.php';
                        require __DIR__.'/../routes/admin/settings.php';
                        require __DIR__.'/../routes/admin/audit-logs.php';
                        require __DIR__.'/../routes/admin/extra-view-requests.php';
                        require __DIR__.'/../routes/admin/device-change-requests.php';
                    });
                });
        }
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->use([
            HandleCors::class,
        ]);

        // Web middleware stack
        $middleware->web([
            SetRequestLocale::class,
        ]);

        // API middleware stack
        $middleware->api([
            SetRequestLocale::class,
            SubstituteBindings::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'jwt.mobile' => JwtMobileMiddleware::class,
            'jwt.admin' => JwtAdminMiddleware::class,
            'setlocale' => SetRequestLocale::class,
            'enrollment.active' => \App\Http\Middleware\EnsureActiveEnrollment::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
