<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Auth\AdminPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AdminAuthController::class, 'login'])->middleware('throttle:admin-login');
Route::post('/auth/password/forgot', [AdminAuthController::class, 'forgotPassword'])->middleware('throttle:admin-forgot');
Route::post('/auth/password/reset', [AdminPasswordResetController::class, 'reset']);
Route::post('/auth/refresh', [AdminAuthController::class, 'refresh'])->middleware('throttle:admin-refresh');

Route::middleware('jwt.admin')->group(function (): void {
    Route::get('/auth/me', [AdminAuthController::class, 'me']);
    Route::post('/auth/change-password', [AdminAuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
});
