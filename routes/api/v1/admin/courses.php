<?php

use App\Http\Controllers\Admin\CourseController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:course.manage')->group(function (): void {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::post('/courses/{course}/clone', [CourseController::class, 'cloneCourse']);
});

Route::post('/courses/{course}/publish', [CourseController::class, 'publish'])
    ->middleware('require.permission:course.publish');
