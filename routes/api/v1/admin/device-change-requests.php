<?php

use App\Http\Controllers\Admin\DeviceChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:device_change.manage', 'scope.system_centerless'])->group(function (): void {
    Route::get('/device-change-requests', [DeviceChangeRequestController::class, 'systemIndex']);
    Route::post('/device-change-requests/{deviceChangeRequest}/approve', [DeviceChangeRequestController::class, 'systemApprove'])
        ->whereNumber('deviceChangeRequest');
    Route::post('/device-change-requests/{deviceChangeRequest}/reject', [DeviceChangeRequestController::class, 'systemReject'])
        ->whereNumber('deviceChangeRequest');
    Route::post('/device-change-requests/{deviceChangeRequest}/pre-approve', [DeviceChangeRequestController::class, 'systemPreApprove'])
        ->whereNumber('deviceChangeRequest');
    Route::post('/students/{student}/device-change-requests', [DeviceChangeRequestController::class, 'systemCreateForStudent'])
        ->whereNumber('student');
    Route::post('/device-change-requests/bulk-approve', [DeviceChangeRequestController::class, 'systemBulkApprove']);
    Route::post('/device-change-requests/bulk-reject', [DeviceChangeRequestController::class, 'systemBulkReject']);
    Route::post('/device-change-requests/bulk-pre-approve', [DeviceChangeRequestController::class, 'systemBulkPreApprove']);
});

Route::middleware(['require.permission:device_change.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/device-change-requests', [DeviceChangeRequestController::class, 'centerIndex'])->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/approve', [DeviceChangeRequestController::class, 'centerApprove'])
        ->whereNumber('center')
        ->whereNumber('deviceChangeRequest');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/reject', [DeviceChangeRequestController::class, 'centerReject'])
        ->whereNumber('center')
        ->whereNumber('deviceChangeRequest');
    Route::post('/centers/{center}/device-change-requests/{deviceChangeRequest}/pre-approve', [DeviceChangeRequestController::class, 'centerPreApprove'])
        ->whereNumber('center')
        ->whereNumber('deviceChangeRequest');
    Route::post('/centers/{center}/students/{student}/device-change-requests', [DeviceChangeRequestController::class, 'centerCreateForStudent'])
        ->whereNumber('center')
        ->whereNumber('student');
    Route::post('/centers/{center}/device-change-requests/bulk-approve', [DeviceChangeRequestController::class, 'centerBulkApprove'])
        ->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/bulk-reject', [DeviceChangeRequestController::class, 'centerBulkReject'])
        ->whereNumber('center');
    Route::post('/centers/{center}/device-change-requests/bulk-pre-approve', [DeviceChangeRequestController::class, 'centerBulkPreApprove'])
        ->whereNumber('center');
});
