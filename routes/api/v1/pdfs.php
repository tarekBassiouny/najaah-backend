<?php

use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\Sections\PublicSectionPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/{course}/pdfs', [CourseController::class, 'listPdfs'])->middleware('enrollment.active');
Route::get('/courses/{course}/pdfs/{pdf}', [CourseController::class, 'showPdf'])->middleware('enrollment.active');

Route::get('/courses/{course}/sections/{section}/pdfs', [PublicSectionPdfController::class, 'index'])->middleware('enrollment.active');
Route::get('/courses/{course}/sections/{section}/pdfs/{pdf}', [PublicSectionPdfController::class, 'show'])->middleware('enrollment.active');
