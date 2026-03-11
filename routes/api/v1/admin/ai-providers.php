<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AI\AIProviderConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin AI Integration Routes
|--------------------------------------------------------------------------
|
| Provider/model management at system scope and center overrides with
| effective options endpoint for AI content generation forms.
|
*/

Route::middleware(['scope.system', 'require.permission:settings.manage'])->group(function (): void {
    Route::get('/ai/providers', [AIProviderConfigController::class, 'systemIndex']);
    Route::put('/ai/providers/{provider}', [AIProviderConfigController::class, 'systemUpdate']);
});

Route::middleware(['scope.center', 'require.permission:settings.manage'])->group(function (): void {
    Route::get('/centers/{center}/ai/providers', [AIProviderConfigController::class, 'centerIndex'])
        ->whereNumber('center');
    Route::put('/centers/{center}/ai/providers/{provider}', [AIProviderConfigController::class, 'centerUpdate'])
        ->whereNumber('center');
});

Route::middleware(['scope.center', 'require.permission:ai_content.generate'])->group(function (): void {
    Route::get('/centers/{center}/ai/options', [AIProviderConfigController::class, 'options'])
        ->whereNumber('center');
});
