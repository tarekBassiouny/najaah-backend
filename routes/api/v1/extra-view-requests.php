<?php

use App\Http\Controllers\Api\V1\ExtraViewRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/extra-view-requests', [ExtraViewRequestController::class, 'index']);
Route::post('/courses/{course}/videos/{video}/extra-view-requests', [ExtraViewRequestController::class, 'store']);
