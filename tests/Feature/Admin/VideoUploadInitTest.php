<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Video;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('creates bunny video and returns upload url', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->with([
            'title' => 'center_'.$center->id.'_course_0_video_0_sample.mp4',
            'meta' => [
                'center_id' => $center->id,
                'course_id' => null,
                'env' => config('app.env'),
            ],
        ], 123)
        ->andReturn([
            'id' => 'bunny-123',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-123',
            'library_id' => 123,
        ]);

    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.video_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('data.library_id', 123)
        ->assertJsonMissing(['upload_url', 'api_key', 'token', 'signed_url', 'cdn_url']);

    $this->assertDatabaseHas('video_upload_sessions', [
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
    ]);
});

it('ties existing video to new upload session', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $admin = $this->asAdmin();
    /** @var Video $video */
    $video = Video::factory()->create([
        'created_by' => $admin->id,
        'encoding_status' => 0,
        'lifecycle_status' => 0,
        'upload_session_id' => null,
    ]);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->with([
            'title' => 'center_'.$center->id.'_course_0_video_'.$video->id.'_sample.mp4',
            'meta' => [
                'center_id' => $center->id,
                'course_id' => null,
                'env' => config('app.env'),
            ],
        ], 123)
        ->andReturn([
            'id' => 'bunny-456',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-456',
            'library_id' => 123,
        ]);

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.video_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonMissing(['upload_url', 'api_key', 'token', 'signed_url', 'cdn_url']);

    $video->refresh();
    expect($video->upload_session_id)->not->toBeNull()
        ->and($video->source_id)->not->toBeNull()
        ->and($video->encoding_status)->toBe(VideoUploadService::STATUS_PENDING);
});

it('rejects upload authorization without admin authentication', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $response = $this->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'sample.mp4',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(401);
});

it('rejects upload when center library is missing', function (): void {
    config([
        'bunny.api.api_key' => 'test-key',
        'bunny.api.api_url' => 'https://video.bunnycdn.com',
        'bunny.api.library_id' => null,
    ]);

    $center = Center::factory()->create();
    $admin = $this->asAdmin();

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});
