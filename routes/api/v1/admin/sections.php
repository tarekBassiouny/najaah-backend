<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Sections\SectionController;
use App\Http\Controllers\Admin\Sections\SectionStructureController;
use App\Http\Controllers\Admin\Sections\SectionWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:section.manage', 'scope.center_route'])
    ->prefix('centers/{center}/courses/{course}/sections')
    ->group(function (): void {
        Route::get('/', [SectionController::class, 'index'])->whereNumber('center');
        Route::post('/', [SectionController::class, 'store'])->whereNumber('center');
        Route::put('/reorder', [SectionController::class, 'reorder'])->whereNumber('center');
        Route::get('/{section}', [SectionController::class, 'show'])->whereNumber('center');
        Route::put('/{section}', [SectionController::class, 'update'])->whereNumber('center');
        Route::delete('/{section}', [SectionController::class, 'destroy'])->whereNumber('center');

        Route::post('/{section}/restore', [SectionController::class, 'restore'])->whereNumber('center');
        Route::patch('/{section}/visibility', [SectionController::class, 'toggleVisibility'])->whereNumber('center');

        Route::post('/structure', [SectionWorkflowController::class, 'createWithStructure'])->whereNumber('center');
        Route::put('/{section}/structure', [SectionWorkflowController::class, 'updateWithStructure'])->whereNumber('center');
        Route::delete('/{section}/structure', [SectionWorkflowController::class, 'deleteWithStructure'])->whereNumber('center');

        Route::get('/{section}/videos', [SectionStructureController::class, 'videos'])->whereNumber('center');
        Route::get('/{section}/videos/{video}', [SectionStructureController::class, 'showVideo'])->whereNumber('center');
        Route::post('/{section}/videos', [SectionStructureController::class, 'attachVideo'])->whereNumber('center');
        Route::delete('/{section}/videos/{video}', [SectionStructureController::class, 'detachVideo'])->whereNumber('center');

        Route::get('/{section}/pdfs', [SectionStructureController::class, 'pdfs'])->whereNumber('center');
        Route::get('/{section}/pdfs/{pdf}', [SectionStructureController::class, 'showPdf'])->whereNumber('center');
        Route::post('/{section}/pdfs', [SectionStructureController::class, 'attachPdf'])->whereNumber('center');
        Route::delete('/{section}/pdfs/{pdf}', [SectionStructureController::class, 'detachPdf'])->whereNumber('center');

        Route::post('/{section}/publish', [SectionWorkflowController::class, 'publish'])->whereNumber('center');
        Route::post('/{section}/unpublish', [SectionWorkflowController::class, 'unpublish'])->whereNumber('center');
    });
