<?php

use App\Http\Controllers\Admin\AgentController;
use Illuminate\Support\Facades\Route;

Route::get('/agents/executions', [AgentController::class, 'index']);
Route::get('/agents/executions/{agentExecution}', [AgentController::class, 'show']);
Route::get('/agents/available', [AgentController::class, 'available']);

Route::post('/agents/execute', [AgentController::class, 'execute']);
Route::post('/agents/content-publishing/execute', [AgentController::class, 'executeContentPublishing']);
Route::post('/agents/enrollment/bulk', [AgentController::class, 'executeBulkEnrollment']);
