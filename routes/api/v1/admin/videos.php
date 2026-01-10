<?php

use App\Http\Controllers\Admin\Videos\VideoController;
use App\Http\Controllers\Admin\Videos\VideoUploadSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:video.manage')->group(function (): void {
    Route::get('/centers/{center}/videos', [VideoController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/videos', [VideoController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/videos/{video}', [VideoController::class, 'show'])->whereNumber('center');
    Route::put('/centers/{center}/videos/{video}', [VideoController::class, 'update'])->whereNumber('center');
    Route::delete('/centers/{center}/videos/{video}', [VideoController::class, 'destroy'])->whereNumber('center');
});

Route::middleware('require.permission:video.upload')->group(function (): void {
    Route::post('/centers/{center}/videos/upload-sessions', [VideoUploadSessionController::class, 'store'])->whereNumber('center');
});
