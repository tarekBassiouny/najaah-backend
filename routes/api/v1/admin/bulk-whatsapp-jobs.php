<?php

use App\Http\Controllers\Admin\BulkWhatsAppJobController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video_access.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/bulk-whatsapp-jobs', [BulkWhatsAppJobController::class, 'centerIndex'])
        ->whereNumber('center');

    Route::get('/centers/{center}/bulk-whatsapp-jobs/{job}', [BulkWhatsAppJobController::class, 'centerShow'])
        ->whereNumber('center')
        ->whereNumber('job');

    Route::post('/centers/{center}/bulk-whatsapp-jobs/{job}/pause', [BulkWhatsAppJobController::class, 'centerPause'])
        ->whereNumber('center')
        ->whereNumber('job');

    Route::post('/centers/{center}/bulk-whatsapp-jobs/{job}/resume', [BulkWhatsAppJobController::class, 'centerResume'])
        ->whereNumber('center')
        ->whereNumber('job');

    Route::post('/centers/{center}/bulk-whatsapp-jobs/{job}/retry-failed', [BulkWhatsAppJobController::class, 'centerRetryFailed'])
        ->whereNumber('center')
        ->whereNumber('job');

    Route::delete('/centers/{center}/bulk-whatsapp-jobs/{job}', [BulkWhatsAppJobController::class, 'centerCancel'])
        ->whereNumber('center')
        ->whereNumber('job');
});
