<?php

use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:audit.view')->group(function (): void {
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('/analytics/courses-media', [AnalyticsController::class, 'coursesMedia']);
    Route::get('/analytics/learners-enrollments', [AnalyticsController::class, 'learnersEnrollments']);
    Route::get('/analytics/devices-requests', [AnalyticsController::class, 'devicesRequests']);
    Route::get('/analytics/students', [AnalyticsController::class, 'students']);
});
