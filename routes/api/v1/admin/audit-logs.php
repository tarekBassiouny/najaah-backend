<?php

use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:audit.view', 'scope.system_admin'])->group(function (): void {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

Route::middleware(['require.permission:audit.view', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/audit-logs', [AuditLogController::class, 'centerIndex'])->whereNumber('center');
});
