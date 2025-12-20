<?php

use App\Http\Controllers\Api\V1\InstructorController;
use Illuminate\Support\Facades\Route;

Route::get('/instructors', [InstructorController::class, 'index']);
Route::get('/instructors/{instructor}', [InstructorController::class, 'show']);
