<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\Admin\VideoUploadSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:video.manage')->group(function (): void {
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/video-upload-sessions', [VideoUploadSessionController::class, 'index']);
});

Route::middleware('require.permission:course.manage')->group(function (): void {
    Route::post('/courses/{course}/videos', [CourseController::class, 'assignVideo']);
    Route::delete('/courses/{course}/videos/{video}', [CourseController::class, 'removeVideo']);
});

Route::middleware('require.permission:video.upload')->group(function (): void {
    Route::post('/video-uploads', [VideoUploadController::class, 'store']);
    Route::patch('/video-uploads/{videoUploadSession}', [VideoUploadController::class, 'update']);
});
