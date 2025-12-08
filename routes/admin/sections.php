<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Sections\SectionController;
use App\Http\Controllers\Admin\Sections\SectionStructureController;
use App\Http\Controllers\Admin\Sections\SectionWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('courses/{course}/sections')->group(function (): void {
    Route::get('/', [SectionController::class, 'index']);
    Route::post('/', [SectionController::class, 'store']);
    Route::put('/reorder', [SectionController::class, 'reorder']);
    Route::get('/{section}', [SectionController::class, 'show']);
    Route::put('/{section}', [SectionController::class, 'update']);
    Route::delete('/{section}', [SectionController::class, 'destroy']);

    Route::post('/{section}/restore', [SectionController::class, 'restore']);
    Route::patch('/{section}/visibility', [SectionController::class, 'toggleVisibility']);

    Route::post('/structure', [SectionWorkflowController::class, 'createWithStructure']);
    Route::put('/{section}/structure', [SectionWorkflowController::class, 'updateWithStructure']);
    Route::delete('/{section}/structure', [SectionWorkflowController::class, 'deleteWithStructure']);

    Route::get('/{section}/videos', [SectionStructureController::class, 'videos']);
    Route::get('/{section}/videos/{video}', [SectionStructureController::class, 'showVideo']);
    Route::post('/{section}/videos', [SectionStructureController::class, 'attachVideo']);
    Route::delete('/{section}/videos/{video}', [SectionStructureController::class, 'detachVideo']);

    Route::get('/{section}/pdfs', [SectionStructureController::class, 'pdfs']);
    Route::get('/{section}/pdfs/{pdf}', [SectionStructureController::class, 'showPdf']);
    Route::post('/{section}/pdfs', [SectionStructureController::class, 'attachPdf']);
    Route::delete('/{section}/pdfs/{pdf}', [SectionStructureController::class, 'detachPdf']);

    Route::post('/{section}/publish', [SectionWorkflowController::class, 'publish']);
    Route::post('/{section}/unpublish', [SectionWorkflowController::class, 'unpublish']);
});
