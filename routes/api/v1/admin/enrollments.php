<?php

use App\Http\Controllers\Admin\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:enrollment.manage')->group(function (): void {
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);
    Route::post('/enrollments', [EnrollmentController::class, 'store']);
    Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
});
