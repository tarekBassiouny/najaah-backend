<?php

use App\Http\Controllers\CenterResolveController;
use Illuminate\Support\Facades\Route;

Route::get('/resolve/centers/{slug}', [CenterResolveController::class, 'show'])
    ->where('slug', '[A-Za-z][A-Za-z0-9-]*');
