<?php

use App\Http\Controllers\Admin\VideoAccessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video_access.manage', 'scope.center'])->group(function (): void {
    Route::delete('/centers/{center}/video-accesses/{access}', [VideoAccessController::class, 'centerRevoke'])
        ->whereNumber('center')
        ->whereNumber('access');
});
