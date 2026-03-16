<?php

use App\Http\Controllers\Admin\VideoCodeBatchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video_access.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/video-code-batches', [VideoCodeBatchController::class, 'index'])
        ->whereNumber('center');

    Route::post('/centers/{center}/courses/{course}/videos/{video}/code-batches', [VideoCodeBatchController::class, 'store'])
        ->whereNumber(['center', 'course', 'video']);

    Route::get('/centers/{center}/code-batches/{batch}', [VideoCodeBatchController::class, 'show'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::post('/centers/{center}/code-batches/{batch}/expand', [VideoCodeBatchController::class, 'expand'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::post('/centers/{center}/code-batches/{batch}/close', [VideoCodeBatchController::class, 'close'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::get('/centers/{center}/code-batches/{batch}/statistics', [VideoCodeBatchController::class, 'statistics'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::get('/centers/{center}/code-batches/{batch}/redemptions', [VideoCodeBatchController::class, 'redemptions'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::get('/centers/{center}/code-batches/{batch}/export/csv', [VideoCodeBatchController::class, 'exportCsv'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::post('/centers/{center}/code-batches/{batch}/send-whatsapp-csv', [VideoCodeBatchController::class, 'sendWhatsAppCsv'])
        ->whereNumber('center')
        ->whereNumber('batch');

    Route::get('/centers/{center}/code-batches/{batch}/export/pdf', [VideoCodeBatchController::class, 'exportPdf'])
        ->whereNumber('center')
        ->whereNumber('batch');
});
