<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Parent\ActivityController;
use App\Http\Controllers\Web\Parent\AssignmentController;
use App\Http\Controllers\Web\Parent\EnrollmentController;
use App\Http\Controllers\Web\Parent\LinkController;
use App\Http\Controllers\Web\Parent\ProgressController;
use App\Http\Controllers\Web\Parent\QuizResultController;
use App\Http\Controllers\Web\Parent\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated Parent Routes
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.web.parent')->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Linked Students
    |--------------------------------------------------------------------------
    */
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show'])->whereNumber('student');

    /*
    |--------------------------------------------------------------------------
    | Link Management
    |--------------------------------------------------------------------------
    */
    Route::get('/links', [LinkController::class, 'index']);
    Route::post('/links', [LinkController::class, 'store'])->middleware('throttle:5,60');

    /*
    |--------------------------------------------------------------------------
    | Student Enrollments
    |--------------------------------------------------------------------------
    */
    Route::get('/students/{student}/enrollments', [EnrollmentController::class, 'index'])->whereNumber('student');

    /*
    |--------------------------------------------------------------------------
    | Course Progress
    |--------------------------------------------------------------------------
    */
    Route::get('/students/{student}/courses/{course}/progress', [ProgressController::class, 'show'])
        ->whereNumber(['student', 'course']);

    /*
    |--------------------------------------------------------------------------
    | Quiz Results
    |--------------------------------------------------------------------------
    */
    Route::get('/students/{student}/courses/{course}/quiz-attempts', [QuizResultController::class, 'index'])
        ->whereNumber(['student', 'course']);
    Route::get('/quiz-attempts/{attempt}', [QuizResultController::class, 'show'])->whereNumber('attempt');

    /*
    |--------------------------------------------------------------------------
    | Assignment Submissions
    |--------------------------------------------------------------------------
    */
    Route::get('/students/{student}/courses/{course}/assignments', [AssignmentController::class, 'index'])
        ->whereNumber(['student', 'course']);

    /*
    |--------------------------------------------------------------------------
    | Weekly Activity
    |--------------------------------------------------------------------------
    */
    Route::get('/students/{student}/centers/{center}/activity/weekly', [ActivityController::class, 'index'])
        ->whereNumber(['student', 'center']);

});
