<?php

use App\Http\Controllers\Admin\Pdfs\PdfController;
use App\Http\Controllers\Admin\Pdfs\PdfUploadSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:pdf.manage', 'scope.center'])->group(function (): void {
    Route::get('/centers/{center}/pdfs', [PdfController::class, 'index'])->whereNumber('center');
    Route::post('/centers/{center}/pdfs', [PdfController::class, 'store'])->whereNumber('center');
    Route::get('/centers/{center}/pdfs/{pdf}', [PdfController::class, 'show'])->whereNumber('center');
    Route::get('/centers/{center}/pdfs/{pdf}/signed-url', [PdfController::class, 'signedUrl'])->whereNumber('center');
    Route::put('/centers/{center}/pdfs/{pdf}', [PdfController::class, 'update'])->whereNumber('center');
    Route::delete('/centers/{center}/pdfs/{pdf}', [PdfController::class, 'destroy'])->whereNumber('center');
    Route::post('/centers/{center}/pdfs/upload-sessions', [PdfUploadSessionController::class, 'store'])->whereNumber('center');
    Route::post('/centers/{center}/pdfs/upload-sessions/{pdfUploadSession}/finalize', [PdfUploadSessionController::class, 'finalize'])->whereNumber('center');
});
