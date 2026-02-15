<?php

use App\Http\Controllers\Admin\Centers\CenterController;
use App\Http\Controllers\Admin\Centers\CenterOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:center.manage', 'require.role:super_admin', 'scope.system_admin'])->group(function (): void {
    Route::get('/centers', [CenterController::class, 'index']);
    Route::post('/centers', [CenterController::class, 'store']);
    Route::get('/centers/{center}', [CenterController::class, 'show'])->whereNumber('center');
    Route::put('/centers/{center}', [CenterController::class, 'update'])->whereNumber('center');
    Route::delete('/centers/{center}', [CenterController::class, 'destroy'])->whereNumber('center');
    Route::post('/centers/{center}/restore', [CenterController::class, 'restore'])->whereNumber('center');
    Route::post('/centers/{center}/onboarding/retry', [CenterOperationsController::class, 'retry'])->whereNumber('center');
    Route::post('/centers/{center}/branding/logo', [CenterOperationsController::class, 'uploadLogo'])->whereNumber('center');
});

Route::middleware(['require.permission:settings.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/settings', [CenterOperationsController::class, 'show'])->whereNumber('center');
    Route::patch('/centers/{center}/settings', [CenterOperationsController::class, 'update'])->whereNumber('center');
});
