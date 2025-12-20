<?php

declare(strict_types=1);

it('returns ok from health check endpoint', function (): void {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.app', true)
        ->assertJsonPath('checks.db', true)
        ->assertJsonPath('checks.cache', true);
});

it('returns ok from up endpoint', function (): void {
    $response = $this->get('/up');

    $response->assertOk();
});
