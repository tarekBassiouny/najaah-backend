<?php

use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\Sections\PublicSectionPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/{course}/pdfs', [CourseController::class, 'listPdfs']);
Route::get('/courses/{course}/pdfs/{pdf}', [CourseController::class, 'showPdf']);

Route::get('/courses/{course}/sections/{section}/pdfs', [PublicSectionPdfController::class, 'index']);
Route::get('/courses/{course}/sections/{section}/pdfs/{pdf}', [PublicSectionPdfController::class, 'show']);
