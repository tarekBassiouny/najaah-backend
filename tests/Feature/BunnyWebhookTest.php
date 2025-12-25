<?php

declare(strict_types=1);

use App\Models\BunnyWebhookLog;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('webhooks');

it('updates upload session and video to ready', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'video-123',
        'library_id' => 123,
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $video = Video::factory()->create([
        'upload_session_id' => $session->id,
        'library_id' => 123,
        'source_id' => 'video-123',
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    $payload = [
        'Status' => 3,
        'VideoGuid' => 'video-123',
        'VideoLibraryId' => 123,
    ];

    $response = $this->postJson('/webhooks/bunny', $payload);

    $response->assertOk();

    $session->refresh();
    $video->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_READY);
    expect($session->library_id)->toBe(123);
    expect($video->encoding_status)->toBe(VideoUploadService::STATUS_READY);
    expect($video->lifecycle_status)->toBe(2);

    $this->assertDatabaseHas('bunny_webhook_logs', [
        'video_guid' => 'video-123',
        'library_id' => 123,
        'status' => 3,
    ]);
});

it('records failure without downgrading ready videos', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'video-999',
        'library_id' => 55,
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $readyVideo = Video::factory()->create([
        'upload_session_id' => $session->id,
        'library_id' => 55,
        'source_id' => 'video-999',
        'encoding_status' => VideoUploadService::STATUS_READY,
        'lifecycle_status' => 2,
    ]);

    $payload = [
        'Status' => 5,
        'VideoGuid' => 'video-999',
        'VideoLibraryId' => 55,
        'ErrorMessage' => 'transcode failed',
    ];

    $response = $this->postJson('/webhooks/bunny', $payload);

    $response->assertOk();

    $session->refresh();
    $readyVideo->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_FAILED);
    expect($session->error_message)->toBe('transcode failed');
    expect($readyVideo->encoding_status)->toBe(VideoUploadService::STATUS_READY);
});

it('ignores duplicate or lower-priority updates once ready', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'dup-1',
        'library_id' => 77,
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $video = Video::factory()->create([
        'upload_session_id' => $session->id,
        'library_id' => 77,
        'source_id' => 'dup-1',
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    $readyPayload = [
        'Status' => 3,
        'VideoGuid' => 'dup-1',
        'VideoLibraryId' => 77,
    ];

    $this->postJson('/webhooks/bunny', $readyPayload)->assertOk();

    $downgradePayload = [
        'Status' => 1,
        'VideoGuid' => 'dup-1',
        'VideoLibraryId' => 77,
    ];

    $this->postJson('/webhooks/bunny', $downgradePayload)->assertOk();

    $session->refresh();
    $video->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_READY);
    expect($video->encoding_status)->toBe(VideoUploadService::STATUS_READY);
});

it('ignores cross-library payloads', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'cross-1',
        'library_id' => 10,
        'upload_status' => VideoUploadService::STATUS_PROCESSING,
    ]);

    $video = Video::factory()->create([
        'upload_session_id' => $session->id,
        'library_id' => 10,
        'source_id' => 'cross-1',
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    $payload = [
        'Status' => 3,
        'VideoGuid' => 'cross-1',
        'VideoLibraryId' => 11,
    ];

    $this->postJson('/webhooks/bunny', $payload)->assertOk();

    $session->refresh();
    $video->refresh();

    expect($session->upload_status)->toBe(VideoUploadService::STATUS_PROCESSING);
    expect($video->encoding_status)->toBe(VideoUploadService::STATUS_PROCESSING);
});

it('logs status but does not mutate for ignored codes', function (): void {
    $payload = [
        'Status' => 9,
        'VideoGuid' => 'ignore-1',
        'VideoLibraryId' => 22,
    ];

    $this->postJson('/webhooks/bunny', $payload)->assertOk();

    $this->assertDatabaseHas('bunny_webhook_logs', [
        'video_guid' => 'ignore-1',
        'library_id' => 22,
        'status' => 9,
    ]);

    expect(BunnyWebhookLog::count())->toBe(1);
});
