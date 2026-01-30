<?php

use App\Http\Controllers\Admin\DeviceChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:device_change.manage')->group(function (): void {
    Route::get('/device-change-requests', [DeviceChangeRequestController::class, 'index']);
    Route::post('/device-change-requests/{deviceChangeRequest}/approve', [DeviceChangeRequestController::class, 'approve']);
    Route::post('/device-change-requests/{deviceChangeRequest}/reject', [DeviceChangeRequestController::class, 'reject']);
    Route::post('/device-change-requests/{deviceChangeRequest}/pre-approve', [DeviceChangeRequestController::class, 'preApprove']);
    Route::post('/students/{student}/device-change-requests', [DeviceChangeRequestController::class, 'createForStudent']);
});
