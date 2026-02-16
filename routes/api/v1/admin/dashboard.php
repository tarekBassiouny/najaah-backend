<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('scope.system_admin')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::middleware('scope.center_route')->group(function (): void {
    Route::get('/centers/{center}/dashboard', [DashboardController::class, 'centerIndex'])->whereNumber('center');
});
