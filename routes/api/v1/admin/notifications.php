<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')
    ->middleware('require.permission:notification.manage')
    ->group(function (): void {
        Route::get('/', [AdminNotificationController::class, 'index']);
        Route::get('/count', [AdminNotificationController::class, 'count']);
        Route::put('/{notification}/read', [AdminNotificationController::class, 'markAsRead']);
        Route::post('/read-all', [AdminNotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [AdminNotificationController::class, 'destroy']);
    });
