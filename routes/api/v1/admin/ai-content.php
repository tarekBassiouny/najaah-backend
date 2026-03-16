<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Assessments\AIContentJobController;
use App\Http\Controllers\Admin\Assessments\LearningAssetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin AI Content Routes
|--------------------------------------------------------------------------
|
| Center-scoped AI generation, review, and publish workflow.
|
*/

Route::middleware(['scope.center', 'require.permission:ai_content.generate'])->group(function (): void {
    Route::get('/centers/{center}/ai-content/jobs', [AIContentJobController::class, 'index'])
        ->whereNumber('center');
    Route::post('/centers/{center}/ai-content/jobs', [AIContentJobController::class, 'store'])
        ->whereNumber('center');
    Route::post('/centers/{center}/ai-content/batches', [AIContentJobController::class, 'storeBatch'])
        ->whereNumber('center');
    Route::get('/centers/{center}/ai-content/jobs/{job}', [AIContentJobController::class, 'show'])
        ->whereNumber(['center', 'job']);
    Route::delete('/centers/{center}/ai-content/jobs/{job}', [AIContentJobController::class, 'destroy'])
        ->whereNumber(['center', 'job']);
});

Route::middleware(['scope.center', 'require.permission:ai_content.review_publish'])->group(function (): void {
    Route::patch('/centers/{center}/ai-content/jobs/{job}/review', [AIContentJobController::class, 'review'])
        ->whereNumber(['center', 'job']);
    Route::post('/centers/{center}/ai-content/jobs/{job}/approve', [AIContentJobController::class, 'approve'])
        ->whereNumber(['center', 'job']);
    Route::post('/centers/{center}/ai-content/jobs/{job}/publish', [AIContentJobController::class, 'publish'])
        ->whereNumber(['center', 'job']);
});

Route::middleware(['scope.center', 'require.permission:learning_asset.manage'])->group(function (): void {
    Route::get('/centers/{center}/courses/{course}/learning-assets', [LearningAssetController::class, 'index'])
        ->whereNumber(['center', 'course']);
    Route::get('/centers/{center}/learning-assets/{asset}', [LearningAssetController::class, 'show'])
        ->whereNumber(['center', 'asset']);
    Route::put('/centers/{center}/learning-assets/{asset}', [LearningAssetController::class, 'update'])
        ->whereNumber(['center', 'asset']);
    Route::patch('/centers/{center}/learning-assets/{asset}/status', [LearningAssetController::class, 'updateStatus'])
        ->whereNumber(['center', 'asset']);
});
