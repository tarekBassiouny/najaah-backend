<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureActiveEnrollment;
use App\Http\Middleware\EnsureUnbrandedStudent;
use App\Http\Middleware\JwtAdminMiddleware;
use App\Http\Middleware\JwtMobileMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\ResolveCenterApiKey;
use App\Http\Middleware\SetRequestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
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
                ->middleware('api')
                ->group(function (): void {
                    require __DIR__.'/../routes/api/v1/mobile.php';
                    require __DIR__.'/../routes/api/v1/resolve.php';
                });

            // Admin (JWT) - canonical /api/v1/admin with backward-compatible /admin alias
            Route::prefix('api/v1/admin')
                ->middleware(['api'])
                ->group(function (): void {
                    require __DIR__.'/../routes/api/v1/admin/auth.php';

                    Route::middleware(['jwt.admin'])->group(function (): void {
                        require __DIR__.'/../routes/api/v1/admin/centers.php';

                        // Other admin routes needs to be reviewed
                        require __DIR__.'/../routes/api/v1/admin/enrollments.php';
                        require __DIR__.'/../routes/api/v1/admin/courses.php';
                        require __DIR__.'/../routes/api/v1/admin/sections.php';
                        require __DIR__.'/../routes/api/v1/admin/videos.php';
                        require __DIR__.'/../routes/api/v1/admin/instructors.php';
                        require __DIR__.'/../routes/api/v1/admin/categories.php';
                        require __DIR__.'/../routes/api/v1/admin/pdfs.php';
                        require __DIR__.'/../routes/api/v1/admin/settings.php';
                        require __DIR__.'/../routes/api/v1/admin/audit-logs.php';
                        require __DIR__.'/../routes/api/v1/admin/analytics.php';
                        require __DIR__.'/../routes/api/v1/admin/extra-view-requests.php';
                        require __DIR__.'/../routes/api/v1/admin/device-change-requests.php';
                        require __DIR__.'/../routes/api/v1/admin/roles.php';
                        require __DIR__.'/../routes/api/v1/admin/admin-users.php';
                        require __DIR__.'/../routes/api/v1/admin/students.php';
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
            RequestIdMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/bunny',
        ]);

        // API middleware stack
        $middleware->api([
            ResolveCenterApiKey::class,
            SetRequestLocale::class,
            SubstituteBindings::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'jwt.mobile' => JwtMobileMiddleware::class,
            'jwt.admin' => JwtAdminMiddleware::class,
            'enrollment.active' => EnsureActiveEnrollment::class,
            'ensure.unbranded.student' => EnsureUnbrandedStudent::class,
            'require.permission' => RequirePermission::class,
            'require.role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (DomainException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                ],
            ], $exception->statusCode());
        });
    })
    ->create();
