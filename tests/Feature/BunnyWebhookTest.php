<?php

declare(strict_types=1);

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Jobs\FetchBunnyVideoMetadataJob;
use App\Models\BunnyWebhookLog;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class)->group('webhooks');

it('updates upload session and video to ready', function (): void {
    Queue::fake();

    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'video-123',
        'library_id' => 123,
        'upload_status' => VideoUploadStatus::Processing,
    ]);

    $video = Video::factory()->create([
        'center_id' => $session->center_id,
        'upload_session_id' => $session->id,
        'library_id' => 123,
        'source_id' => 'video-123',
        'encoding_status' => VideoUploadStatus::Processing,
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

    expect($session->upload_status)->toBe(VideoUploadStatus::Ready);
    expect($session->library_id)->toBe(123);
    expect($video->encoding_status)->toBe(VideoUploadStatus::Ready);
    expect($video->lifecycle_status)->toBe(VideoLifecycleStatus::Ready);
    Queue::assertPushed(FetchBunnyVideoMetadataJob::class, function (FetchBunnyVideoMetadataJob $job) use ($session): bool {
        return $job->videoGuid === 'video-123'
            && $job->libraryId === 123
            && $job->centerId === (int) $session->center_id;
    });

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
        'upload_status' => VideoUploadStatus::Processing,
    ]);

    $readyVideo = Video::factory()->create([
        'center_id' => $session->center_id,
        'upload_session_id' => $session->id,
        'library_id' => 55,
        'source_id' => 'video-999',
        'encoding_status' => VideoUploadStatus::Ready,
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

    expect($session->upload_status)->toBe(VideoUploadStatus::Failed);
    expect($session->error_message)->toBe('transcode failed');
    expect($readyVideo->encoding_status)->toBe(VideoUploadStatus::Ready);
});

it('ignores duplicate or lower-priority updates once ready', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'dup-1',
        'library_id' => 77,
        'upload_status' => VideoUploadStatus::Processing,
    ]);

    $video = Video::factory()->create([
        'center_id' => $session->center_id,
        'upload_session_id' => $session->id,
        'library_id' => 77,
        'source_id' => 'dup-1',
        'encoding_status' => VideoUploadStatus::Processing,
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

    expect($session->upload_status)->toBe(VideoUploadStatus::Ready);
    expect($video->encoding_status)->toBe(VideoUploadStatus::Ready);
});

it('ignores cross-library payloads', function (): void {
    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'cross-1',
        'library_id' => 10,
        'upload_status' => VideoUploadStatus::Processing,
    ]);

    $video = Video::factory()->create([
        'center_id' => $session->center_id,
        'upload_session_id' => $session->id,
        'library_id' => 10,
        'source_id' => 'cross-1',
        'encoding_status' => VideoUploadStatus::Processing,
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

    expect($session->upload_status)->toBe(VideoUploadStatus::Processing);
    expect($video->encoding_status)->toBe(VideoUploadStatus::Processing);
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

it('dispatches thumbnail metadata job when webhook is ready', function (): void {
    Queue::fake();

    config(['bunny.thumbnail_base_url' => 'https://stream-cdn.example.com']);

    $session = VideoUploadSession::factory()->create([
        'bunny_upload_id' => 'thumb-1',
        'library_id' => 88,
        'upload_status' => VideoUploadStatus::Processing,
    ]);

    $video = Video::factory()->create([
        'center_id' => $session->center_id,
        'upload_session_id' => $session->id,
        'library_id' => 88,
        'source_id' => 'thumb-1',
        'thumbnail_url' => null,
        'encoding_status' => VideoUploadStatus::Processing,
        'lifecycle_status' => 1,
    ]);

    $this->postJson('/webhooks/bunny', [
        'Status' => 3,
        'VideoGuid' => 'thumb-1',
        'VideoLibraryId' => 88,
    ])->assertOk();

    Queue::assertPushed(FetchBunnyVideoMetadataJob::class, function (FetchBunnyVideoMetadataJob $job) use ($session): bool {
        return $job->videoGuid === 'thumb-1'
            && $job->libraryId === 88
            && $job->centerId === (int) $session->center_id;
    });
});
