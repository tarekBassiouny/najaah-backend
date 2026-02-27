<?php

declare(strict_types=1);

use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('creates bunny video and returns upload url', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $admin = $this->asAdmin();
    // System super admin (center_id = null) can access any center via center routes
    $video = Video::factory()->create([
        'center_id' => $center->id,
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
        ], 123, \Mockery::type('int'))
        ->andReturn([
            'id' => 'bunny-123',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-123',
            'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
            'presigned_headers' => [
                'AuthorizationSignature' => 'test-signature',
                'AuthorizationExpire' => time() + 10800,
                'VideoId' => 'bunny-123',
                'LibraryId' => 123,
            ],
            'library_id' => 123,
            'raw' => [],
        ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.id', fn ($id) => is_int($id) || is_numeric($id))
        ->assertJsonPath('data.provider', 'bunny')
        ->assertJsonPath('data.remote_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('data.upload_endpoint', 'https://video.bunnycdn.com/tusupload')
        ->assertJsonStructure(['data' => ['presigned_headers' => ['AuthorizationSignature', 'AuthorizationExpire', 'VideoId', 'LibraryId']]]);

    $this->assertDatabaseHas('video_upload_sessions', [
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
    ]);
});

it('ties existing video to new upload session', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $admin = $this->asAdmin();
    // System super admin (center_id = null) can access any center via center routes
    /** @var Video $video */
    $video = Video::factory()->create([
        'center_id' => $center->id,
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
        ], 123, \Mockery::type('int'))
        ->andReturn([
            'id' => 'bunny-456',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-456',
            'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
            'presigned_headers' => [
                'AuthorizationSignature' => 'test-signature',
                'AuthorizationExpire' => time() + 10800,
                'VideoId' => 'bunny-456',
                'LibraryId' => 123,
            ],
            'library_id' => 123,
            'raw' => [],
        ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.remote_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('data.upload_endpoint', 'https://video.bunnycdn.com/tusupload');

    $video->refresh();
    expect($video->upload_session_id)->not->toBeNull()
        ->and($video->source_id)->not->toBeNull()
        ->and($video->encoding_status)->toBe(VideoUploadStatus::Pending);
});

it('rejects upload authorization without admin authentication', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => User::factory()->create(['center_id' => $center->id]),
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
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
    // System super admin (center_id = null) can access any center via center routes
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => null,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('creates a new upload session on retry', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();

    $admin = $this->asAdmin();
    // System super admin (center_id = null) can access any center via center routes
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'encoding_status' => 0,
        'lifecycle_status' => 0,
        'upload_session_id' => null,
    ]);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->twice()
        ->andReturn(
            [
                'id' => 'bunny-111',
                'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-111',
                'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
                'presigned_headers' => [
                    'AuthorizationSignature' => 'test-signature-1',
                    'AuthorizationExpire' => time() + 10800,
                    'VideoId' => 'bunny-111',
                    'LibraryId' => 123,
                ],
                'library_id' => 123,
                'raw' => [],
            ],
            [
                'id' => 'bunny-222',
                'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-222',
                'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
                'presigned_headers' => [
                    'AuthorizationSignature' => 'test-signature-2',
                    'AuthorizationExpire' => time() + 10800,
                    'VideoId' => 'bunny-222',
                    'LibraryId' => 123,
                ],
                'library_id' => 123,
                'raw' => [],
            ]
        );

    $first = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());
    $first->assertCreated();

    $second = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/centers/{$center->id}/videos/upload-sessions", [
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ], $this->adminHeaders());
    $second->assertCreated();

    $video->refresh();
    expect($video->upload_session_id)->not->toBeNull()
        ->and($video->source_id)->toBe('bunny-222');

    $this->assertDatabaseCount('video_upload_sessions', 2);
});
