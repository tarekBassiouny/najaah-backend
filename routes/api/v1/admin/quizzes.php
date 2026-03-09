<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Assessments\AIGenerationController;
use App\Http\Controllers\Admin\Assessments\QuizAnalyticsController;
use App\Http\Controllers\Admin\Assessments\QuizController;
use App\Http\Controllers\Admin\Assessments\QuizQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Quiz Routes
|--------------------------------------------------------------------------
|
| Center-scoped quiz management endpoints.
|
*/

Route::middleware(['require.permission:quiz.manage', 'scope.center'])->group(function (): void {
    // Quiz CRUD
    Route::get('/centers/{center}/courses/{course}/quizzes', [QuizController::class, 'index'])
        ->whereNumber(['center', 'course']);
    Route::post('/centers/{center}/courses/{course}/quizzes', [QuizController::class, 'store'])
        ->whereNumber(['center', 'course']);

    Route::get('/centers/{center}/quizzes/{quiz}', [QuizController::class, 'show'])
        ->whereNumber(['center', 'quiz']);
    Route::put('/centers/{center}/quizzes/{quiz}', [QuizController::class, 'update'])
        ->whereNumber(['center', 'quiz']);
    Route::delete('/centers/{center}/quizzes/{quiz}', [QuizController::class, 'destroy'])
        ->whereNumber(['center', 'quiz']);
    Route::post('/centers/{center}/quizzes/{quiz}/duplicate', [QuizController::class, 'duplicate'])
        ->whereNumber(['center', 'quiz']);

    // Quiz Questions
    Route::get('/centers/{center}/quizzes/{quiz}/questions', [QuizQuestionController::class, 'index'])
        ->whereNumber(['center', 'quiz']);
    Route::post('/centers/{center}/quizzes/{quiz}/questions', [QuizQuestionController::class, 'store'])
        ->whereNumber(['center', 'quiz']);
    Route::put('/centers/{center}/quizzes/{quiz}/questions/{question}', [QuizQuestionController::class, 'update'])
        ->whereNumber(['center', 'quiz', 'question']);
    Route::delete('/centers/{center}/quizzes/{quiz}/questions/{question}', [QuizQuestionController::class, 'destroy'])
        ->whereNumber(['center', 'quiz', 'question']);
    Route::put('/centers/{center}/quizzes/{quiz}/questions/reorder', [QuizQuestionController::class, 'reorder'])
        ->whereNumber(['center', 'quiz']);

    // AI Generation
    Route::post('/centers/{center}/quizzes/{quiz}/generate-from-video', [AIGenerationController::class, 'generateFromVideo'])
        ->whereNumber(['center', 'quiz']);
    Route::post('/centers/{center}/quizzes/{quiz}/generate-from-pdf', [AIGenerationController::class, 'generateFromPdf'])
        ->whereNumber(['center', 'quiz']);
    Route::get('/centers/{center}/ai-generation-jobs/{aiGenerationJob}', [AIGenerationController::class, 'show'])
        ->whereNumber(['center', 'aiGenerationJob']);
    Route::post('/centers/{center}/ai-generation-jobs/{aiGenerationJob}/approve', [AIGenerationController::class, 'approve'])
        ->whereNumber(['center', 'aiGenerationJob']);
    Route::delete('/centers/{center}/ai-generation-jobs/{aiGenerationJob}', [AIGenerationController::class, 'discard'])
        ->whereNumber(['center', 'aiGenerationJob']);

    // Quiz Analytics
    Route::get('/centers/{center}/quizzes/{quiz}/attempts', [QuizAnalyticsController::class, 'attempts'])
        ->whereNumber(['center', 'quiz']);
    Route::get('/centers/{center}/quizzes/{quiz}/analytics', [QuizAnalyticsController::class, 'analytics'])
        ->whereNumber(['center', 'quiz']);
    Route::get('/centers/{center}/quizzes/{quiz}/attempts/{attempt}', [QuizAnalyticsController::class, 'attemptDetail'])
        ->whereNumber(['center', 'quiz', 'attempt']);
});
