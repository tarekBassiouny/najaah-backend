<?php

use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\ParentLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:parents.view', 'scope.center'])
    ->prefix('/centers/{center}/parents')
    ->whereNumber('center')
    ->group(function (): void {
        Route::get('/', [ParentController::class, 'centerIndex']);
        Route::get('/pending-requests', [ParentController::class, 'pendingRequests']);
        Route::get('/{parent}', [ParentController::class, 'centerShow'])->whereNumber('parent');
    });

Route::middleware(['require.permission:parents.manage', 'scope.center'])
    ->prefix('/centers/{center}/parent-links')
    ->whereNumber('center')
    ->group(function (): void {
        Route::post('/', [ParentLinkController::class, 'store']);
        Route::patch('/{link}', [ParentLinkController::class, 'update'])->whereNumber('link');
    });

Route::middleware(['require.permission:parents.view', 'scope.center'])
    ->prefix('/students/{student}/parent-links')
    ->whereNumber('student')
    ->group(function (): void {
        Route::get('/', [ParentController::class, 'studentLinks']);
    });
