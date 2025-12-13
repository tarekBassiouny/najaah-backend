<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('webhooks');

function signPayload(array $payload): string
{
    $secret = config('bunny.api.webhook_secret', 'secret');

    return hash_hmac('sha256', json_encode($payload), $secret);
}

it('updates upload session and video to ready', function (): void {
    config(['bunny.api.webhook_secret' => 'secret']);
    $center = Center::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'bunny_upload_id' => 'video-123',
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $video = Video::factory()->create([
        'upload_session_id' => $session->id,
        'source_id' => 'video-123',
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    $payload = [
        'Event' => 'EncodingFinished',
        'VideoGuid' => 'video-123',
        'LibraryId' => 'lib-1',
    ];

    $response = $this->postJson('/api/webhooks/bunny', $payload, [
        'Bunny-Signature' => signPayload($payload),
    ]);

    $response->assertOk();

    $session->refresh();
    $video->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_READY);
    expect($video->encoding_status)->toBe(VideoUploadService::STATUS_READY);
    expect($video->lifecycle_status)->toBe(2);
});

it('records failure without downgrading ready videos', function (): void {
    config(['bunny.api.webhook_secret' => 'secret']);
    $center = Center::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create();

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'bunny_upload_id' => 'video-999',
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $readyVideo = Video::factory()->create([
        'upload_session_id' => $session->id,
        'source_id' => 'video-999',
        'encoding_status' => VideoUploadService::STATUS_READY,
        'lifecycle_status' => 2,
    ]);

    $payload = [
        'Event' => 'EncodingFailed',
        'VideoGuid' => 'video-999',
        'LibraryId' => 'lib-1',
        'ErrorMessage' => 'transcode failed',
    ];

    $response = $this->postJson('/api/webhooks/bunny', $payload, [
        'Bunny-Signature' => signPayload($payload),
    ]);

    $response->assertOk();

    $session->refresh();
    $readyVideo->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_FAILED);
    expect($session->error_message)->toBe('transcode failed');
    expect($readyVideo->encoding_status)->toBe(VideoUploadService::STATUS_READY);
});

it('rejects invalid signature', function (): void {
    $payload = [
        'Event' => 'EncodingFinished',
        'VideoGuid' => 'video-x',
        'LibraryId' => 'lib-1',
    ];

    $response = $this->postJson('/api/webhooks/bunny', $payload, [
        'Bunny-Signature' => 'invalid',
    ]);

    $response->assertStatus(401);
});
