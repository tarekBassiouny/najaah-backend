<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Assessments\AssignmentController;
use App\Http\Controllers\Admin\Assessments\AssignmentSubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Assignment Routes
|--------------------------------------------------------------------------
|
| Center-scoped assignment management endpoints.
|
*/

Route::middleware(['require.permission:assignment.manage', 'scope.center'])->group(function (): void {
    // Assignment CRUD
    Route::get('/centers/{center}/courses/{course}/assignments', [AssignmentController::class, 'index'])
        ->whereNumber(['center', 'course']);
    Route::post('/centers/{center}/courses/{course}/assignments', [AssignmentController::class, 'store'])
        ->whereNumber(['center', 'course']);

    Route::get('/centers/{center}/assignments/{assignment}', [AssignmentController::class, 'show'])
        ->whereNumber(['center', 'assignment']);
    Route::put('/centers/{center}/assignments/{assignment}', [AssignmentController::class, 'update'])
        ->whereNumber(['center', 'assignment']);
    Route::delete('/centers/{center}/assignments/{assignment}', [AssignmentController::class, 'destroy'])
        ->whereNumber(['center', 'assignment']);

    // Submissions
    Route::get('/centers/{center}/assignments/{assignment}/submissions', [AssignmentSubmissionController::class, 'index'])
        ->whereNumber(['center', 'assignment']);
    Route::get('/centers/{center}/assignments/{assignment}/statistics', [AssignmentSubmissionController::class, 'statistics'])
        ->whereNumber(['center', 'assignment']);

    Route::get('/centers/{center}/submissions/{submission}', [AssignmentSubmissionController::class, 'show'])
        ->whereNumber(['center', 'submission']);
    Route::post('/centers/{center}/submissions/{submission}/grade', [AssignmentSubmissionController::class, 'grade'])
        ->whereNumber(['center', 'submission']);
    Route::post('/centers/{center}/submissions/{submission}/return', [AssignmentSubmissionController::class, 'returnForRevision'])
        ->whereNumber(['center', 'submission']);
    Route::get('/centers/{center}/submissions/{submission}/files/{file}/download', [AssignmentSubmissionController::class, 'downloadFile'])
        ->whereNumber(['center', 'submission', 'file']);
});
