<?php

use App\Http\Controllers\Admin\ExtraViewRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:extra_view.manage')->group(function (): void {
    Route::get('/extra-view-requests', [ExtraViewRequestController::class, 'index']);
    Route::post('/extra-view-requests/{extraViewRequest}/approve', [ExtraViewRequestController::class, 'approve']);
    Route::post('/extra-view-requests/{extraViewRequest}/reject', [ExtraViewRequestController::class, 'reject']);
});
