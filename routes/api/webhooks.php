<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/evolution', [EvolutionWebhookController::class, 'handle']);
