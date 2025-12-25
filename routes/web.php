<?php

// backend/routes/web.php
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Webhooks\BunnyWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'health']);
Route::get('/up', [HealthController::class, 'up']);

Route::post('/webhooks/bunny', [BunnyWebhookController::class, 'handle']);
