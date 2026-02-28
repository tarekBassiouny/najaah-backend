<?php

declare(strict_types=1);

use App\Models\EvolutionWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('webhooks');

it('accepts evolution webhook requests with the configured secret', function (): void {
    config([
        'evolution.webhook_secret' => 'test-secret',
        'evolution.webhook_secret_header' => 'x-evolution-secret',
    ]);

    $response = $this->postJson('/webhooks/evolution', [
        'event' => 'CONNECTION_UPDATE',
        'instance' => 'najaah-local',
        'data' => [
            'state' => 'open',
        ],
    ], [
        'x-evolution-secret' => 'test-secret',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('evolution_webhook_logs', [
        'instance' => 'najaah-local',
        'event' => 'CONNECTION_UPDATE',
        'status' => 'accepted',
        'response_code' => 200,
    ]);

    expect(EvolutionWebhookLog::query()->count())->toBe(1);
});

it('rejects evolution webhook requests with an invalid secret', function (): void {
    config([
        'evolution.webhook_secret' => 'test-secret',
        'evolution.webhook_secret_header' => 'x-evolution-secret',
    ]);

    $response = $this->postJson('/webhooks/evolution', [
        'event' => 'CONNECTION_UPDATE',
        'instance' => 'najaah-local',
        'data' => [
            'state' => 'open',
        ],
    ], [
        'x-evolution-secret' => 'wrong-secret',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Evolution webhook secret is invalid.',
        ]);

    $this->assertDatabaseHas('evolution_webhook_logs', [
        'instance' => 'najaah-local',
        'event' => 'CONNECTION_UPDATE',
        'status' => 'rejected',
        'response_code' => 401,
        'error_message' => 'Evolution webhook secret is invalid.',
    ]);
});
