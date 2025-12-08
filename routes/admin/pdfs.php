<?php

use App\Http\Controllers\Admin\CourseController;
use Illuminate\Support\Facades\Route;

Route::post('/courses/{course}/pdfs', [CourseController::class, 'assignPdf']);
Route::delete('/courses/{course}/pdfs/{pdf}', [CourseController::class, 'removePdf']);
