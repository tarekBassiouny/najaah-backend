<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Student Authentication (OTP + JWT)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('/send-otp', [OtpController::class, 'send']);
        Route::post('/verify', [LoginController::class, 'verify']);
        Route::post('/refresh', [TokenController::class, 'refresh']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Authentication (Sanctum SPA)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/auth')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login']);
        Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [AdminAuthController::class, 'me'])->middleware('auth:sanctum');
    });
});
