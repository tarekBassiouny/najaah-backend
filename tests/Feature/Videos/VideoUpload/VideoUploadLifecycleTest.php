<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('initializes an upload session for admin', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 123,
    ]);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->with(['title' => 'intro.mp4'], 123)
        ->andReturn([
            'id' => 'bunny-789',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-789',
            'library_id' => 123,
        ]);

    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'intro.mp4',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.video_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('data.library_id', 123)
        ->assertJsonMissing(['upload_url', 'api_key', 'token', 'signed_url', 'cdn_url']);

    $this->assertDatabaseHas('video_upload_sessions', [
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
    ]);
});

it('moves upload session to ready and updates video', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 123,
    ]);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->with(['title' => 'lesson.mp4'], 123)
        ->andReturn([
            'id' => 'bunny-abc',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-abc',
            'library_id' => 123,
        ]);

    $admin = $this->asAdmin();
    /** @var Video $video */
    $video = Video::factory()->create([
        'lifecycle_status' => 0,
        'encoding_status' => 0,
        'created_by' => $admin->id,
    ]);

    $create = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'video_id' => $video->id,
        'original_filename' => 'lesson.mp4',
    ]);

    /** @var VideoUploadSession $session */
    $session = VideoUploadSession::where('center_id', $center->id)
        ->where('uploaded_by', $admin->id)
        ->latest()
        ->firstOrFail();
    $sessionId = $session->id;

    $update = $this->actingAs($admin, 'admin')->patchJson("/api/v1/admin/video-uploads/{$sessionId}", [
        'status' => 'READY',
        'source_id' => 'bunny-123',
        'duration_seconds' => 180,
    ]);

    $update->assertOk()
        ->assertJsonPath('data.upload_status', VideoUploadService::STATUS_READY);

    $video->refresh();
    expect($video->encoding_status)->toBe(3)
        ->and($video->lifecycle_status)->toBe(2)
        ->and($video->source_id)->toBe('bunny-123')
        ->and($video->duration_seconds)->toBe(180);
});

it('records failures and keeps video inactive', function (): void {
    $center = Center::factory()->create([
        'bunny_library_id' => 123,
    ]);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->with(['title' => 'broken.mp4'], 123)
        ->andReturn([
            'id' => 'bunny-def',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-def',
            'library_id' => 123,
        ]);

    $admin = $this->asAdmin();

    $create = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'broken.mp4',
    ]);

    /** @var VideoUploadSession $session */
    $session = VideoUploadSession::where('center_id', $center->id)
        ->where('uploaded_by', $admin->id)
        ->latest()
        ->firstOrFail();
    $sessionId = $session->id;

    $video = Video::factory()->create([
        'lifecycle_status' => 1,
        'encoding_status' => 1,
        'upload_session_id' => $session->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->patchJson("/api/v1/admin/video-uploads/{$sessionId}", [
        'status' => 'FAILED',
        'error_message' => 'Encoding failed',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.upload_status', VideoUploadService::STATUS_FAILED)
        ->assertJsonPath('data.error_message', 'Encoding failed');

    $video->refresh();
    expect($video->encoding_status)->toBe(0)
        ->and($video->lifecycle_status)->toBe(0);
});
