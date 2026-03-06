<?php

use App\Http\Controllers\Mobile\Education\CollegeLookupController;
use App\Http\Controllers\Mobile\Education\GradeLookupController;
use App\Http\Controllers\Mobile\Education\SchoolLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.mobile')->group(function (): void {
    Route::get('/centers/{center}/grades', [GradeLookupController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/schools', [SchoolLookupController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/colleges', [CollegeLookupController::class, 'index'])->whereNumber('center');
});
