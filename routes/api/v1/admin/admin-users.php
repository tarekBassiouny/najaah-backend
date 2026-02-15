<?php

use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:admin.manage', 'scope.system_admin'])->group(function (): void {
    Route::get('/users', [AdminUserController::class, 'systemIndex']);
    Route::post('/users', [AdminUserController::class, 'systemStore']);
    Route::put('/users/{user}', [AdminUserController::class, 'systemUpdate']);
    Route::delete('/users/{user}', [AdminUserController::class, 'systemDestroy']);
    Route::put('/users/{user}/roles', [AdminUserController::class, 'systemSyncRoles'])
        ->middleware('require.role:super_admin');
});

Route::middleware(['require.permission:admin.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/users', [AdminUserController::class, 'centerIndex'])->whereNumber('center');
    Route::post('/centers/{center}/users', [AdminUserController::class, 'centerStore'])->whereNumber('center');
    Route::put('/centers/{center}/users/{user}', [AdminUserController::class, 'centerUpdate'])->whereNumber('center');
    Route::delete('/centers/{center}/users/{user}', [AdminUserController::class, 'centerDestroy'])->whereNumber('center');
    Route::put('/centers/{center}/users/{user}/roles', [AdminUserController::class, 'centerSyncRoles'])
        ->whereNumber('center')
        ->middleware('require.role:super_admin');
});
