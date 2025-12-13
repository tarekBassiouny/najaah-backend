<?php

use App\Http\Controllers\Api\V1\PlaybackController;
use App\Http\Controllers\Api\V1\PlaybackSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/courses/{course}/videos/{video}/playback/authorize', [PlaybackController::class, 'authorize']);
Route::patch('/playback/sessions/{session}', [PlaybackSessionController::class, 'update']);
Route::post('/playback/sessions/{session}/end', [PlaybackSessionController::class, 'end']);
