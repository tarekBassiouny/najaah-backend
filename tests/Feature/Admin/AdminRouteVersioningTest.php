<?php

declare(strict_types=1);

it('exposes admin login via versioned and legacy prefixes', function (): void {
    $this->postJson('/api/v1/admin/auth/login', [], [
        'X-Api-Key' => config('services.system_api_key'),
    ])->assertStatus(422);
});

it('requires authentication on protected admin routes for both prefixes', function (): void {
    $this->getJson('/api/v1/admin/audit-logs', [
        'X-Api-Key' => config('services.system_api_key'),
    ])->assertStatus(401);
});
