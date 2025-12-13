<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\BunnyWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/bunny', [BunnyWebhookController::class, 'handle']);
