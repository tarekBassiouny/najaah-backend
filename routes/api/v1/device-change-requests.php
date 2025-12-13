<?php

use App\Http\Controllers\Api\V1\DeviceChangeRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/device-change-requests', [DeviceChangeRequestController::class, 'index']);
Route::post('/device-change-requests', [DeviceChangeRequestController::class, 'store']);
