<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\ParentAuthController;
use App\Http\Controllers\Web\Auth\StudentAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Student Auth (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/student')->group(function (): void {
    Route::post('/send-otp', [StudentAuthController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('/verify', [StudentAuthController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::post('/refresh', [StudentAuthController::class, 'refresh']);
});

/*
|--------------------------------------------------------------------------
| Web Student Auth (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/student')->middleware('jwt.web.student')->group(function (): void {
    Route::get('/me', [StudentAuthController::class, 'me']);
    Route::post('/logout', [StudentAuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Web Parent Auth (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/parent')->group(function (): void {
    Route::post('/register', [ParentAuthController::class, 'register'])->middleware('throttle:otp-send');
    Route::post('/send-otp', [ParentAuthController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('/verify', [ParentAuthController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::post('/refresh', [ParentAuthController::class, 'refresh']);
});

/*
|--------------------------------------------------------------------------
| Web Parent Auth (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/parent')->middleware('jwt.web.parent')->group(function (): void {
    Route::get('/me', [ParentAuthController::class, 'me']);
    Route::post('/logout', [ParentAuthController::class, 'logout']);
});
