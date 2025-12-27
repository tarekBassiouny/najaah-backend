<?php

use App\Http\Controllers\Admin\Centers\CenterBrandingController;
use App\Http\Controllers\Admin\Centers\CenterController;
use App\Http\Controllers\Admin\Centers\CenterOnboardingController;
use App\Http\Controllers\Admin\Centers\CenterSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:center.manage', 'require.role:super_admin'])->group(function (): void {
    Route::get('/centers', [CenterController::class, 'index']);
    Route::post('/centers', [CenterController::class, 'store']);
    Route::get('/centers/{center}', [CenterController::class, 'show'])->whereNumber('center');
    Route::put('/centers/{center}', [CenterController::class, 'update'])->whereNumber('center');
    Route::delete('/centers/{center}', [CenterController::class, 'destroy'])->whereNumber('center');
    Route::post('/centers/{center}/restore', [CenterController::class, 'restore'])->whereNumber('center');
    Route::post('/centers/{center}/onboarding/retry', [CenterOnboardingController::class, 'retry'])->whereNumber('center');
    Route::post('/centers/{center}/branding/logo', [CenterBrandingController::class, 'uploadLogo'])->whereNumber('center');
});

Route::middleware('require.permission:settings.manage')->group(function (): void {
    Route::get('/centers/{center}/settings', [CenterSettingsController::class, 'show'])->whereNumber('center');
    Route::patch('/centers/{center}/settings', [CenterSettingsController::class, 'update'])->whereNumber('center');
});
