<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPasswordResetController;
use App\Http\Controllers\Admin\CenterController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AdminAuthController::class, 'login']);
Route::post('/auth/password/reset', [AdminPasswordResetController::class, 'reset']);

Route::middleware('jwt.admin')->group(function (): void {
    Route::get('/auth/me', [AdminAuthController::class, 'me']);
    Route::post('/auth/refresh', [AdminAuthController::class, 'refresh']);
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);

    Route::middleware(['require.permission:center.manage', 'require.role:super_admin'])->group(function (): void {
        Route::get('/centers', [CenterController::class, 'index']);
        Route::post('/centers', [CenterController::class, 'store']);
        Route::get('/centers/{center}', [CenterController::class, 'show']);
        Route::put('/centers/{center}', [CenterController::class, 'update']);
        Route::delete('/centers/{center}', [CenterController::class, 'destroy']);
        Route::post('/centers/{center}/restore', [CenterController::class, 'restore']);
    });
});
