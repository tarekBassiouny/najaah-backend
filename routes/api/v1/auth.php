<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/send-otp', [OtpController::class, 'send']);
Route::post('/auth/verify', [LoginController::class, 'verify']);
Route::post('/auth/refresh', [TokenController::class, 'refresh']);
