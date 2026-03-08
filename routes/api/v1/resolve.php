<?php

use App\Http\Controllers\CenterResolveController;
use App\Http\Controllers\LandingPageResolveController;
use Illuminate\Support\Facades\Route;

Route::get('/resolve/centers/{slug}', [CenterResolveController::class, 'show'])
    ->where('slug', '[A-Za-z][A-Za-z0-9-]*');

Route::get('/resolve/landing-page/{slug}', [LandingPageResolveController::class, 'show'])
    ->where('slug', '[A-Za-z][A-Za-z0-9-]*');
