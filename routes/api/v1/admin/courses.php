<?php

use App\Http\Controllers\Admin\Course\CourseController;
use App\Http\Controllers\Admin\Course\CourseOperationController;
use Illuminate\Support\Facades\Route;

Route::middleware('require.permission:course.manage')->group(function (): void {
    Route::middleware('scope.center')->group(function (): void {
        Route::get('/centers/{center}/courses', [CourseController::class, 'centerIndex'])->whereNumber('center');
        Route::post('/centers/{center}/courses', [CourseController::class, 'centerStore'])->whereNumber('center');
        Route::get('/centers/{center}/courses/{course}', [CourseController::class, 'centerShow'])->whereNumber('center');
        Route::put('/centers/{center}/courses/{course}', [CourseController::class, 'centerUpdate'])->whereNumber('center');
        Route::delete('/centers/{center}/courses/{course}', [CourseController::class, 'centerDestroy'])->whereNumber('center');
        Route::post('/centers/{center}/courses/{course}/clone', [CourseOperationController::class, 'cloneCourse'])->whereNumber('center');
        Route::post('/centers/{center}/courses/{course}/videos', [CourseOperationController::class, 'assignVideo'])->whereNumber('center');
        Route::delete('/centers/{center}/courses/{course}/videos/{video}', [CourseOperationController::class, 'removeVideo'])->whereNumber('center');
        Route::post('/centers/{center}/courses/{course}/pdfs', [CourseOperationController::class, 'assignPdf'])->whereNumber('center');
        Route::delete('/centers/{center}/courses/{course}/pdfs/{pdf}', [CourseOperationController::class, 'removePdf'])->whereNumber('center');
        Route::post('/centers/{center}/courses/{course}/thumbnail', [CourseOperationController::class, 'uploadThumbnail'])->whereNumber('center');
    });
});

Route::post('/centers/{center}/courses/{course}/publish', [CourseOperationController::class, 'publish'])
    ->middleware(['require.permission:course.publish', 'scope.center'])
    ->whereNumber('center');

Route::post('/centers/{center}/courses/{course}/unpublish', [CourseOperationController::class, 'unpublish'])
    ->middleware(['require.permission:course.publish', 'scope.center'])
    ->whereNumber('center');
