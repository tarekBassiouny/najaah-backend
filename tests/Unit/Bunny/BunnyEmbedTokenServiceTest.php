<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Bunny\BunnyEmbedTokenService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class)->group('bunny', 'services', 'tokens');

afterEach(function (): void {
    Carbon::setTestNow();
});

it('generates a valid embed token payload with deterministic output', function (): void {
    config([
        'bunny.api.api_key' => 'bunny-secret',
    ]);

    Carbon::setTestNow('2024-01-01 00:00:00');

    $student = User::factory()->create(['is_student' => true]);
    $service = new BunnyEmbedTokenService;

    $result = $service->generate('video-uuid', $student, 600);

    expect($result['expires_in'])->toBe(600);

    $decoded = decodeEmbedToken($result['token']);
    $expectedExpiry = now()->addSeconds(600)->timestamp;

    expect($decoded['video_uuid'])->toBe('video-uuid')
        ->and((int) $decoded['student_id'])->toBe($student->id)
        ->and((int) $decoded['expires_at'])->toBe($expectedExpiry);

    $expectedPayload = $decoded['video_uuid'].'|'.$decoded['student_id'].'|'.$decoded['expires_at'];
    $expectedHash = hash_hmac('sha256', $expectedPayload, 'bunny-secret');

    expect($decoded['hash'])->toBe($expectedHash);

    $repeat = $service->generate('video-uuid', $student, 600);
    expect($repeat['token'])->toBe($result['token']);
});

/**
 * @return array{video_uuid:string,student_id:string,expires_at:string,hash:string}
 */
function decodeEmbedToken(string $token): array
{
    $base64 = strtr($token, '-_', '+/');
    $base64 .= str_repeat('=', (4 - (strlen($base64) % 4)) % 4);

    $decoded = base64_decode($base64, true);
    expect($decoded)->not->toBeFalse();

    $parts = explode('|', (string) $decoded);
    expect($parts)->toHaveCount(4);

    return [
        'video_uuid' => $parts[0],
        'student_id' => $parts[1],
        'expires_at' => $parts[2],
        'hash' => $parts[3],
    ];
}
