<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Auth\AdminPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AdminAuthController::class, 'login']);
Route::post('/auth/password/reset', [AdminPasswordResetController::class, 'reset']);

Route::middleware('jwt.admin')->group(function (): void {
    Route::get('/auth/me', [AdminAuthController::class, 'me']);
    Route::post('/auth/refresh', [AdminAuthController::class, 'refresh']);
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
});
