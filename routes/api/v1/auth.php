<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/send-otp', [OtpController::class, 'send'])
    ->middleware('resolve.center.api-key');
Route::post('/auth/verify', [LoginController::class, 'verify'])
    ->middleware('resolve.center.api-key');
Route::post('/auth/refresh', [TokenController::class, 'refresh']);

Route::middleware('jwt.mobile')->group(function (): void {
    Route::get('/auth/me', MeController::class);
    Route::post('/auth/logout', LogoutController::class);
});
