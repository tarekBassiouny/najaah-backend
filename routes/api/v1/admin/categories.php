<?php

use App\Http\Controllers\Admin\Categories\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:course.manage'])->group(function (): void {
    Route::get('/centers/{center}/categories', [CategoryController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/categories', [CategoryController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/categories/{category}', [CategoryController::class, 'show'])->whereNumber('center')->whereNumber('category');
    Route::put('/centers/{center}/categories/{category}', [CategoryController::class, 'update'])->whereNumber('center')->whereNumber('category');
    Route::delete('/centers/{center}/categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('center')->whereNumber('category');
});
