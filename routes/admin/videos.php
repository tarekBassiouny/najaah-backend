<?php

use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\Admin\VideoUploadSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/courses/{course}/videos', [CourseController::class, 'assignVideo']);
Route::delete('/courses/{course}/videos/{video}', [CourseController::class, 'removeVideo']);
Route::get('/video-upload-sessions', [VideoUploadSessionController::class, 'index']);
Route::post('/video-uploads', [VideoUploadController::class, 'store']);
Route::patch('/video-uploads/{videoUploadSession}', [VideoUploadController::class, 'update']);
