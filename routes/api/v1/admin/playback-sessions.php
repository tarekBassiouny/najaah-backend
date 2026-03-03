<?php

use App\Http\Controllers\Admin\PlaybackSessions\PlaybackSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/playback-sessions', [PlaybackSessionController::class, 'index'])
        ->whereNumber('center');
});
