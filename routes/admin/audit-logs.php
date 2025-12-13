<?php

use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditLogController::class, 'index']);
