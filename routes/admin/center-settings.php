<?php

use App\Http\Controllers\Admin\CenterSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/centers/{center}/settings', [CenterSettingsController::class, 'show']);
Route::patch('/centers/{center}/settings', [CenterSettingsController::class, 'update']);
