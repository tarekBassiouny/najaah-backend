<?php

use App\Http\Controllers\Admin\Roles\PermissionController;
use App\Http\Controllers\Admin\Roles\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:role.manage', 'scope.system'])->group(function (): void {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
    Route::post('/roles/permissions/bulk', [RoleController::class, 'bulkSyncPermissions']);
});

Route::middleware(['require.permission:role.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/roles', [RoleController::class, 'centerIndex'])->whereNumber('center');
    Route::get('/centers/{center}/roles/{role}', [RoleController::class, 'centerShow'])->whereNumber('center');
    Route::post('/centers/{center}/roles', [RoleController::class, 'centerStore'])->whereNumber('center');
    Route::put('/centers/{center}/roles/{role}', [RoleController::class, 'centerUpdate'])->whereNumber('center');
    Route::delete('/centers/{center}/roles/{role}', [RoleController::class, 'centerDestroy'])->whereNumber('center');
    Route::put('/centers/{center}/roles/{role}/permissions', [RoleController::class, 'centerSyncPermissions'])->whereNumber('center');
    Route::post('/centers/{center}/roles/permissions/bulk', [RoleController::class, 'centerBulkSyncPermissions'])->whereNumber('center');
});

Route::get('/permissions', [PermissionController::class, 'index'])
    ->middleware('require.permission:permission.view');
