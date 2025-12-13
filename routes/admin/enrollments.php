<?php

use App\Http\Controllers\Admin\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::post('/enrollments', [EnrollmentController::class, 'store']);
Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
