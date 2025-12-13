<?php

use App\Http\Controllers\Admin\DeviceChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/device-change-requests/{deviceChangeRequest}/approve', [DeviceChangeRequestController::class, 'approve']);
Route::post('/device-change-requests/{deviceChangeRequest}/reject', [DeviceChangeRequestController::class, 'reject']);
