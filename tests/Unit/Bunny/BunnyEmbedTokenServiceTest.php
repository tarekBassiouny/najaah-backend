<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Bunny\BunnyEmbedTokenService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class)->group('bunny');

test('it generates a bunny embed token with expected hash and expiry', function (): void {
    config(['bunny.embed_key' => 'embed-secret']);
    Carbon::setTestNow('2025-01-01 00:00:00');

    $service = app(BunnyEmbedTokenService::class);
    $user = new User;
    $user->id = 42;

    $result = $service->generate('video-uuid', $user, 10, 99, 300);

    $expectedExpires = Carbon::now()->addSeconds(300)->timestamp;
    $expectedToken = hash('sha256', 'embed-secret'.'video-uuid'.$expectedExpires);

    expect($result['expires'])->toBe($expectedExpires)
        ->and($result['token'])->toBe($expectedToken);

    Carbon::setTestNow();
});

test('it throws when bunny embed key is missing', function (): void {
    config(['bunny.embed_key' => null]);

    $service = app(BunnyEmbedTokenService::class);
    $user = new User;
    $user->id = 1;

    expect(fn (): array => $service->generate('video-uuid', $user, 1, 1))
        ->toThrow(RuntimeException::class);
});
