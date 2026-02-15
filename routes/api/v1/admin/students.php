<?php

use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:student.manage', 'scope.system_admin'])->group(function (): void {
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{user}/profile', [StudentProfileController::class, 'show']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{user}', [StudentController::class, 'update']);
    Route::delete('/students/{user}', [StudentController::class, 'destroy']);
    Route::post('/students/bulk-status', [StudentController::class, 'bulkUpdateStatus']);
});

Route::middleware(['require.permission:student.manage', 'scope.center_route'])
    ->prefix('/centers/{center}/students')
    ->whereNumber('center')
    ->group(function (): void {
        Route::get('/', [StudentController::class, 'centerIndex']);
        Route::post('/', [StudentController::class, 'centerStore']);
        Route::get('/{user}/profile', [StudentProfileController::class, 'centerShow']);
        Route::put('/{user}', [StudentController::class, 'centerUpdate']);
        Route::delete('/{user}', [StudentController::class, 'centerDestroy']);
        Route::post('/bulk-status', [StudentController::class, 'centerBulkUpdateStatus']);
    });
