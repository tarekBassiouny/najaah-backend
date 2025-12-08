<?php

use App\Http\Controllers\Admin\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AdminAuthController::class, 'login']);

Route::middleware('jwt.admin')->group(function () {
    Route::get('/auth/me', [AdminAuthController::class, 'me']);
    Route::post('/auth/refresh', [AdminAuthController::class, 'refresh']);
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
});
