<?php

use App\Http\Controllers\Admin\Education\CollegeController;
use App\Http\Controllers\Admin\Education\GradeController;
use App\Http\Controllers\Admin\Education\SchoolController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:student.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/grades', [GradeController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/grades', [GradeController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/grades/lookup', [GradeController::class, 'lookup'])->whereNumber('center');
    Route::get('/centers/{center}/grades/{grade}', [GradeController::class, 'show'])->whereNumber('center')->whereNumber('grade');
    Route::put('/centers/{center}/grades/{grade}', [GradeController::class, 'update'])->whereNumber('center')->whereNumber('grade');
    Route::delete('/centers/{center}/grades/{grade}', [GradeController::class, 'destroy'])->whereNumber('center')->whereNumber('grade');

    Route::get('/centers/{center}/schools', [SchoolController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/schools', [SchoolController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/schools/lookup', [SchoolController::class, 'lookup'])->whereNumber('center');
    Route::get('/centers/{center}/schools/{school}', [SchoolController::class, 'show'])->whereNumber('center')->whereNumber('school');
    Route::put('/centers/{center}/schools/{school}', [SchoolController::class, 'update'])->whereNumber('center')->whereNumber('school');
    Route::delete('/centers/{center}/schools/{school}', [SchoolController::class, 'destroy'])->whereNumber('center')->whereNumber('school');

    Route::get('/centers/{center}/colleges', [CollegeController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/colleges', [CollegeController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/colleges/lookup', [CollegeController::class, 'lookup'])->whereNumber('center');
    Route::get('/centers/{center}/colleges/{college}', [CollegeController::class, 'show'])->whereNumber('center')->whereNumber('college');
    Route::put('/centers/{center}/colleges/{college}', [CollegeController::class, 'update'])->whereNumber('center')->whereNumber('college');
    Route::delete('/centers/{center}/colleges/{college}', [CollegeController::class, 'destroy'])->whereNumber('center')->whereNumber('college');
});
