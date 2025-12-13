<?php

declare(strict_types=1);

it('exposes admin login via versioned and legacy prefixes', function (): void {
    $this->postJson('/api/v1/admin/auth/login', [])->assertStatus(422);
    $this->postJson('/admin/auth/login', [])->assertStatus(422);
});

it('requires authentication on protected admin routes for both prefixes', function (): void {
    $this->getJson('/api/v1/admin/courses')->assertStatus(401);
    $this->getJson('/admin/courses')->assertStatus(401);
});
