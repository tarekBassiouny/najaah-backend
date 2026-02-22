<?php

use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:admin.manage', 'scope.system'])->group(function (): void {
    Route::get('/users', [AdminUserController::class, 'systemIndex']);
    Route::post('/users', [AdminUserController::class, 'systemStore']);
    Route::post('/users/bulk-status', [AdminUserController::class, 'systemBulkUpdateStatus']);
    Route::post('/users/roles/bulk', [AdminUserController::class, 'systemBulkSyncRoles'])
        ->middleware('require.role:super_admin');
    Route::post('/users/assign-center/bulk', [AdminUserController::class, 'systemBulkAssignCenters'])
        ->middleware('require.role:super_admin');
    Route::match(['post', 'put'], '/users/bulk-assign-centers', [AdminUserController::class, 'systemBulkAssignCenters'])
        ->middleware('require.role:super_admin');
    Route::put('/users/{user}', [AdminUserController::class, 'systemUpdate'])->whereNumber('user');
    Route::put('/users/{user}/status', [AdminUserController::class, 'systemUpdateStatus'])->whereNumber('user');
    Route::delete('/users/{user}', [AdminUserController::class, 'systemDestroy'])->whereNumber('user');
    Route::put('/users/{user}/roles', [AdminUserController::class, 'systemSyncRoles'])
        ->whereNumber('user')
        ->middleware('require.role:super_admin');
    Route::put('/users/{user}/assign-center', [AdminUserController::class, 'systemAssignCenter'])
        ->whereNumber('user')
        ->middleware('require.role:super_admin');
});

Route::middleware(['require.permission:admin.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/users', [AdminUserController::class, 'centerIndex'])->whereNumber('center');
    Route::post('/centers/{center}/users', [AdminUserController::class, 'centerStore'])->whereNumber('center');
    Route::put('/centers/{center}/users/{user}', [AdminUserController::class, 'centerUpdate'])->whereNumber('center')->whereNumber('user');
    Route::put('/centers/{center}/users/{user}/status', [AdminUserController::class, 'centerUpdateStatus'])->whereNumber('center')->whereNumber('user');
    Route::post('/centers/{center}/users/bulk-status', [AdminUserController::class, 'centerBulkUpdateStatus'])->whereNumber('center');
    Route::delete('/centers/{center}/users/{user}', [AdminUserController::class, 'centerDestroy'])->whereNumber('center')->whereNumber('user');
    Route::put('/centers/{center}/users/{user}/roles', [AdminUserController::class, 'centerSyncRoles'])
        ->whereNumber('center')
        ->whereNumber('user');
    Route::post('/centers/{center}/users/roles/bulk', [AdminUserController::class, 'centerBulkSyncRoles'])
        ->whereNumber('center');
});
