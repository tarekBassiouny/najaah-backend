<?php

declare(strict_types=1);

it('adds X-Request-Id header when missing', function () {
    $response = $this->getJson('/health');

    $response->assertHeader('X-Request-Id');
    expect($response->headers->get('X-Request-Id'))->not->toBeEmpty();
});

it('preserves incoming X-Request-Id header', function () {
    $response = $this
        ->withHeader('X-Request-Id', 'req-test-123')
        ->getJson('/health');

    $response->assertHeader('X-Request-Id', 'req-test-123');
});

it('accepts X-Request-ID and normalizes it', function () {
    $response = $this
        ->withHeader('X-Request-ID', 'req-upper-456')
        ->getJson('/health');

    $response->assertHeader('X-Request-Id', 'req-upper-456');
});

it('includes X-Request-Id on validation errors', function () {
    $response = $this->postJson('/api/v1/admin/auth/login', [], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(422);
    $response->assertHeader('X-Request-Id');
});

it('includes X-Request-Id on authorization failures', function () {
    $response = $this->getJson('/api/v1/admin/centers', [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(401);
    $response->assertHeader('X-Request-Id');
});

it('includes X-Request-Id on up endpoint', function () {
    $response = $this->getJson('/up');

    $response->assertOk();
    $response->assertHeader('X-Request-Id');
});
