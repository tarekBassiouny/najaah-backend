<?php

use App\Http\Controllers\Admin\VideoAccessRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video_access.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/video-access-requests', [VideoAccessRequestController::class, 'centerIndex'])
        ->whereNumber('center');

    Route::post('/centers/{center}/video-access-requests/{videoAccessRequest}/approve', [VideoAccessRequestController::class, 'centerApprove'])
        ->whereNumber('center')
        ->whereNumber('videoAccessRequest');

    Route::post('/centers/{center}/video-access-requests/{videoAccessRequest}/reject', [VideoAccessRequestController::class, 'centerReject'])
        ->whereNumber('center')
        ->whereNumber('videoAccessRequest');

    Route::post('/centers/{center}/video-access-requests/bulk-approve', [VideoAccessRequestController::class, 'centerBulkApprove'])
        ->whereNumber('center');

    Route::post('/centers/{center}/video-access-requests/bulk-reject', [VideoAccessRequestController::class, 'centerBulkReject'])
        ->whereNumber('center');
});
