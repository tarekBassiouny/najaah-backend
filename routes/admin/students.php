<?php

use App\Http\Controllers\Admin\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:student.manage')->group(function (): void {
    Route::get('/students', [StudentController::class, 'index']);
    Route::put('/students/{user}', [StudentController::class, 'update']);
});

Route::middleware(['require.permission:student.manage', 'require.role:super_admin'])->group(function (): void {
    Route::post('/students', [StudentController::class, 'store']);
    Route::delete('/students/{user}', [StudentController::class, 'destroy']);
});
