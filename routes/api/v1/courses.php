<?php

use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\CourseInstructorController;
use App\Http\Controllers\Api\V1\InstructorController;
use Illuminate\Support\Facades\Route;

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show'])->middleware('enrollment.active');

Route::apiResource('instructors', InstructorController::class);
Route::post('/courses/{course}/instructors', [CourseInstructorController::class, 'store']);
Route::delete('/courses/{course}/instructors/{instructor}', [CourseInstructorController::class, 'destroy']);
