<?php

use App\Http\Controllers\Admin\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:enrollment.manage', 'scope.system_centerless'])->group(function (): void {
    Route::get('/enrollments', [EnrollmentController::class, 'systemIndex']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'systemShow'])->whereNumber('enrollment');
    Route::post('/enrollments', [EnrollmentController::class, 'systemStore']);
    Route::post('/enrollments/bulk', [EnrollmentController::class, 'systemBulkEnroll']);
    Route::post('/enrollments/bulk-status', [EnrollmentController::class, 'systemBulkUpdateStatus']);
    Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'systemUpdate'])->whereNumber('enrollment');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'systemDestroy'])->whereNumber('enrollment');
});

Route::middleware(['require.permission:enrollment.manage', 'scope.center_route'])
    ->prefix('/centers/{center}/enrollments')
    ->whereNumber('center')
    ->group(function (): void {
        Route::get('/', [EnrollmentController::class, 'centerIndex']);
        Route::get('/{enrollment}', [EnrollmentController::class, 'centerShow'])->whereNumber('enrollment');
        Route::post('/', [EnrollmentController::class, 'centerStore']);
        Route::post('/bulk', [EnrollmentController::class, 'centerBulkEnroll']);
        Route::post('/bulk-status', [EnrollmentController::class, 'centerBulkUpdateStatus']);
        Route::put('/{enrollment}', [EnrollmentController::class, 'centerUpdate'])->whereNumber('enrollment');
        Route::delete('/{enrollment}', [EnrollmentController::class, 'centerDestroy'])->whereNumber('enrollment');
    });
