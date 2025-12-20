<?php

use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Api\V1\CourseInstructorController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:instructor.manage')->group(function (): void {
    Route::apiResource('instructors', InstructorController::class);
    Route::post('/courses/{course}/instructors', [CourseInstructorController::class, 'store']);
    Route::delete('/courses/{course}/instructors/{instructor}', [CourseInstructorController::class, 'destroy']);
});
