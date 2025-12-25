<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\AdminAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class)->group('auth', 'services', 'admin');

test('login returns user and token', function (): void {

    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'secret123',
    ]);

    $service = new AdminAuthService;
    $result = $service->login('admin@example.com', 'secret123');

    expect($result)->not()->toBeNull();
    if ($result !== null) {
        expect($result['user']->id)->toBe($user->id);
        expect($result['token'])->toBeString();
    }
});

test('login returns null on invalid credentials', function (): void {
    $service = new AdminAuthService;
    $result = $service->login('invalid@example.com', 'wrong');
    expect($result)->toBeNull();
});
