<?php

use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\Sections\PublicSectionVideoController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/{course}/videos', [CourseController::class, 'listVideos']);
Route::get('/courses/{course}/videos/{video}', [CourseController::class, 'showVideo']);

Route::get('/courses/{course}/sections/{section}/videos', [PublicSectionVideoController::class, 'index']);
Route::get('/courses/{course}/sections/{section}/videos/{video}', [PublicSectionVideoController::class, 'show']);
