<?php

use App\Http\Controllers\Admin\Roles\PermissionController;
use App\Http\Controllers\Admin\Roles\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:role.manage')->group(function (): void {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
});

Route::middleware(['require.permission:role.manage', 'scope.system_admin'])->group(function (): void {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
});

Route::get('/permissions', [PermissionController::class, 'index'])
    ->middleware('require.permission:permission.view');
