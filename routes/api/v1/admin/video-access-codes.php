<?php

use App\Http\Controllers\Admin\VideoAccessCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:video_access.manage', 'scope.system'])->group(function (): void {
    Route::post('/students/{student}/video-access-codes', [VideoAccessCodeController::class, 'systemGenerateForStudent'])
        ->whereNumber('student');

    Route::post('/video-access-codes/bulk', [VideoAccessCodeController::class, 'systemBulkGenerate']);
});

Route::middleware(['require.permission:video_access.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/video-access-codes', [VideoAccessCodeController::class, 'centerIndex'])
        ->whereNumber('center');

    Route::post('/centers/{center}/students/{student}/video-access-codes', [VideoAccessCodeController::class, 'centerGenerateForStudent'])
        ->whereNumber('center')
        ->whereNumber('student');

    Route::post('/centers/{center}/video-access-codes/bulk', [VideoAccessCodeController::class, 'centerBulkGenerate'])
        ->whereNumber('center');

    Route::get('/centers/{center}/video-access-codes/{code}', [VideoAccessCodeController::class, 'centerShow'])
        ->whereNumber('center')
        ->whereNumber('code');

    Route::post('/centers/{center}/video-access-codes/{code}/regenerate', [VideoAccessCodeController::class, 'centerRegenerate'])
        ->whereNumber('center')
        ->whereNumber('code');

    Route::post('/centers/{center}/video-access-codes/{code}/revoke', [VideoAccessCodeController::class, 'centerRevoke'])
        ->whereNumber('center')
        ->whereNumber('code');

    Route::post('/centers/{center}/video-access-codes/bulk-revoke', [VideoAccessCodeController::class, 'centerBulkRevoke'])
        ->whereNumber('center');

    Route::post('/centers/{center}/video-access-codes/{code}/send-whatsapp', [VideoAccessCodeController::class, 'centerSendWhatsApp'])
        ->whereNumber('center')
        ->whereNumber('code');

    Route::post('/centers/{center}/video-access-codes/bulk-send-whatsapp', [VideoAccessCodeController::class, 'centerBulkSendWhatsApp'])
        ->whereNumber('center');
});
