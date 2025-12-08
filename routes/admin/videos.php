<?php

use App\Http\Controllers\Admin\CourseController;
use Illuminate\Support\Facades\Route;

Route::post('/courses/{course}/videos', [CourseController::class, 'assignVideo']);
Route::delete('/courses/{course}/videos/{video}', [CourseController::class, 'removeVideo']);
