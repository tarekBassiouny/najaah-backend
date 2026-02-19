<?php

use App\Http\Controllers\Admin\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:enrollment.manage', 'scope.center_route'])
    ->prefix('/centers/{center}/enrollments')
    ->whereNumber('center')
    ->group(function (): void {
        Route::get('/', [EnrollmentController::class, 'index']);
        Route::get('/{enrollment}', [EnrollmentController::class, 'show'])->whereNumber('enrollment');
        Route::post('/', [EnrollmentController::class, 'store']);
        Route::post('/bulk', [EnrollmentController::class, 'bulk']);
        Route::put('/{enrollment}', [EnrollmentController::class, 'update'])->whereNumber('enrollment');
        Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy'])->whereNumber('enrollment');
    });
