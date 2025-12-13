<?php

use App\Http\Controllers\Admin\ExtraViewRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/extra-view-requests/{extraViewRequest}/approve', [ExtraViewRequestController::class, 'approve']);
Route::post('/extra-view-requests/{extraViewRequest}/reject', [ExtraViewRequestController::class, 'reject']);
