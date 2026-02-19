<?php

use App\Http\Controllers\Admin\DeviceChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:device_change.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/device-change-requests', [DeviceChangeRequestController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/approve', [DeviceChangeRequestController::class, 'approve'])->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/reject', [DeviceChangeRequestController::class, 'reject'])->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/pre-approve', [DeviceChangeRequestController::class, 'preApprove'])->whereNumber('center');
    Route::post('/centers/{center}/students/{student}/device-change-requests', [DeviceChangeRequestController::class, 'createForStudent'])->whereNumber('center');
});
