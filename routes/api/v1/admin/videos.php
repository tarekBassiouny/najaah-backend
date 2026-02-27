<?php

use App\Http\Controllers\Admin\Videos\VideoController;
use App\Http\Controllers\Admin\Videos\VideoUploadSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/videos', [VideoController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/videos', [VideoController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/videos/{video}', [VideoController::class, 'show'])->whereNumber(['center', 'video']);
    Route::put('/centers/{center}/videos/{video}', [VideoController::class, 'update'])->whereNumber(['center', 'video']);
    Route::delete('/centers/{center}/videos/{video}', [VideoController::class, 'destroy'])->whereNumber(['center', 'video']);
    Route::post('/centers/{center}/videos/{video}/preview', [VideoController::class, 'preview'])->whereNumber(['center', 'video']);
});

Route::middleware(['require.permission:video.upload', 'scope.center'])->group(function (): void {
    Route::post('/centers/{center}/videos/create-upload', [VideoUploadSessionController::class, 'createUpload'])->whereNumber('center');
    Route::get('/centers/{center}/videos/upload-sessions', [VideoUploadSessionController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/videos/upload-sessions/{videoUploadSession}', [VideoUploadSessionController::class, 'show'])->whereNumber(['center', 'videoUploadSession']);
    Route::post('/centers/{center}/videos/upload-sessions', [VideoUploadSessionController::class, 'store'])->whereNumber('center');
});
