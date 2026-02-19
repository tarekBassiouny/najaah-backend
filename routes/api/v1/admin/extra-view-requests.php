<?php

use App\Http\Controllers\Admin\ExtraViewRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:extra_view.manage', 'scope.system_centerless'])->group(function (): void {
    Route::get('/extra-view-requests', [ExtraViewRequestController::class, 'systemIndex']);
    Route::post('/students/{student}/extra-view-grants', [ExtraViewRequestController::class, 'systemGrantForStudent'])
        ->whereNumber('student');
    Route::post('/extra-view-grants/bulk', [ExtraViewRequestController::class, 'systemBulkGrant']);
    Route::post('/extra-view-requests/{extraViewRequest}/approve', [ExtraViewRequestController::class, 'systemApprove'])
        ->whereNumber('extraViewRequest');
    Route::post('/extra-view-requests/{extraViewRequest}/reject', [ExtraViewRequestController::class, 'systemReject'])
        ->whereNumber('extraViewRequest');
    Route::post('/extra-view-requests/bulk-approve', [ExtraViewRequestController::class, 'systemBulkApprove']);
    Route::post('/extra-view-requests/bulk-reject', [ExtraViewRequestController::class, 'systemBulkReject']);
});

Route::middleware(['require.permission:extra_view.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/extra-view-requests', [ExtraViewRequestController::class, 'centerIndex'])->whereNumber('center');
    Route::post('/centers/{center}/students/{student}/extra-view-grants', [ExtraViewRequestController::class, 'centerGrantForStudent'])
        ->whereNumber('center')
        ->whereNumber('student');
    Route::post('/centers/{center}/extra-view-grants/bulk', [ExtraViewRequestController::class, 'centerBulkGrant'])
        ->whereNumber('center');
    Route::post('/centers/{center}/extra-view-requests/{extraViewRequest}/approve', [ExtraViewRequestController::class, 'centerApprove'])
        ->whereNumber('center')
        ->whereNumber('extraViewRequest');
    Route::post('/centers/{center}/extra-view-requests/{extraViewRequest}/reject', [ExtraViewRequestController::class, 'centerReject'])
        ->whereNumber('center')
        ->whereNumber('extraViewRequest');
    Route::post('/centers/{center}/extra-view-requests/bulk-approve', [ExtraViewRequestController::class, 'centerBulkApprove'])
        ->whereNumber('center');
    Route::post('/centers/{center}/extra-view-requests/bulk-reject', [ExtraViewRequestController::class, 'centerBulkReject'])
        ->whereNumber('center');
});
