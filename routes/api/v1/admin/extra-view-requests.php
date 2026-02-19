<?php

use App\Http\Controllers\Admin\ExtraViewRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:extra_view.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/extra-view-requests', [ExtraViewRequestController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/extra-view-requests/{extraViewRequest}/approve', [ExtraViewRequestController::class, 'approve'])->whereNumber('center');
    Route::post('/centers/{center}/extra-view-requests/{extraViewRequest}/reject', [ExtraViewRequestController::class, 'reject'])->whereNumber('center');
});
