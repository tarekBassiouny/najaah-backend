<?php

use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:audit.view', 'scope.system'])->group(function (): void {
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('/analytics/courses-media', [AnalyticsController::class, 'coursesMedia']);
    Route::get('/analytics/learners-enrollments', [AnalyticsController::class, 'learnersEnrollments']);
    Route::get('/analytics/devices-requests', [AnalyticsController::class, 'devicesRequests']);
    Route::get('/analytics/students', [AnalyticsController::class, 'students']);
});

Route::middleware(['require.permission:audit.view', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/analytics/overview', [AnalyticsController::class, 'centerOverview'])->whereNumber('center');
    Route::get('/centers/{center}/analytics/courses-media', [AnalyticsController::class, 'centerCoursesMedia'])->whereNumber('center');
    Route::get('/centers/{center}/analytics/learners-enrollments', [AnalyticsController::class, 'centerLearnersEnrollments'])->whereNumber('center');
    Route::get('/centers/{center}/analytics/devices-requests', [AnalyticsController::class, 'centerDevicesRequests'])->whereNumber('center');
    Route::get('/centers/{center}/analytics/students', [AnalyticsController::class, 'centerStudents'])->whereNumber('center');
});
