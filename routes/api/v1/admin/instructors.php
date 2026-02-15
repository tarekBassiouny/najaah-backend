<?php

use App\Http\Controllers\Admin\CourseInstructorController;
use App\Http\Controllers\Admin\InstructorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:instructor.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/instructors', [InstructorController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/instructors', [InstructorController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/instructors/{instructor}', [InstructorController::class, 'show'])->whereNumber('center');
    Route::put('/centers/{center}/instructors/{instructor}', [InstructorController::class, 'update'])->whereNumber('center');
    Route::delete('/centers/{center}/instructors/{instructor}', [InstructorController::class, 'destroy'])->whereNumber('center');
    Route::post('/centers/{center}/courses/{course}/instructors', [CourseInstructorController::class, 'store'])->whereNumber('center');
    Route::delete('/centers/{center}/courses/{course}/instructors/{instructor}', [CourseInstructorController::class, 'destroy'])->whereNumber('center');
});
