<?php

use App\Http\Controllers\Api\V1\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/enrollments', [EnrollmentController::class, 'index']);
