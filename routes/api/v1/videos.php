<?php

use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\Sections\PublicSectionVideoController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/{course}/videos', [CourseController::class, 'listVideos'])->middleware('enrollment.active');
Route::get('/courses/{course}/videos/{video}', [CourseController::class, 'showVideo'])->middleware('enrollment.active');

Route::get('/courses/{course}/sections/{section}/videos', [PublicSectionVideoController::class, 'index'])->middleware('enrollment.active');
Route::get('/courses/{course}/sections/{section}/videos/{video}', [PublicSectionVideoController::class, 'show'])->middleware('enrollment.active');
