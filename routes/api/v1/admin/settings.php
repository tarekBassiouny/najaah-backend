<?php

use App\Http\Controllers\Admin\SettingsPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/settings/preview', SettingsPreviewController::class)
    ->middleware('require.permission:settings.manage');
