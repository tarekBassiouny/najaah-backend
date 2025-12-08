<?php

use App\Http\Controllers\Api\V1\Sections\PublicSectionController;
use Illuminate\Support\Facades\Route;

Route::get('/courses/{course}/sections', [PublicSectionController::class, 'index']);
Route::get('/courses/{course}/sections/{section}', [PublicSectionController::class, 'show']);
