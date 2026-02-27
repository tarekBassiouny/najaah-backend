<?php

declare(strict_types=1);

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Bunny\BunnyStreamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('videos');

it('creates video and upload session in one request', function (): void {
    config(['bunny.api.library_id' => 123]);

    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $this->mock(BunnyStreamService::class)
        ->shouldReceive('createVideo')
        ->once()
        ->andReturn([
            'id' => 'bunny-create-upload-1',
            'upload_url' => 'https://video.bunnycdn.com/library/123/videos/bunny-create-upload-1',
            'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
            'presigned_headers' => [
                'AuthorizationSignature' => 'sig',
                'AuthorizationExpire' => time() + 10800,
                'VideoId' => 'bunny-create-upload-1',
                'LibraryId' => 123,
            ],
            'library_id' => 123,
            'raw' => [],
        ]);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/create-upload",
        [
            'title_translations' => ['en' => 'Wizard Video'],
            'description_translations' => ['en' => 'Upload me'],
            'tags' => ['wizard'],
            'original_filename' => 'wizard.mp4',
            'file_size_bytes' => 1024 * 1024,
            'mime_type' => 'video/mp4',
        ],
        $this->adminHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.video.title', 'Wizard Video')
        ->assertJsonPath('data.video.encoding_status_key', 'pending')
        ->assertJsonPath('data.upload_session.id', fn ($value) => is_int($value) || is_numeric($value))
        ->assertJsonPath('data.upload_session.upload_endpoint', 'https://video.bunnycdn.com/tusupload');

    $videoId = (int) $response->json('data.video.id');
    $video = Video::find($videoId);
    expect($video)->not->toBeNull()
        ->and($video?->center_id)->toBe($center->id)
        ->and($video?->upload_session_id)->not->toBeNull();
});

it('validates create-upload size and mime type', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/videos/create-upload",
        [
            'title_translations' => ['en' => 'Big Video'],
            'original_filename' => 'huge.mov',
            'file_size_bytes' => 2147483649,
            'mime_type' => 'application/octet-stream',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file_size_bytes', 'mime_type']);
});

it('lists upload sessions with active filter and shows linked video status', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $active = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Processing,
        'progress_percent' => 88,
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $active->id,
        'duration_seconds' => 133,
        'encoding_status' => VideoUploadStatus::Processing,
        'lifecycle_status' => VideoLifecycleStatus::Processing,
        'thumbnail_url' => null,
    ]);

    VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Ready,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(
        "/api/v1/admin/centers/{$center->id}/videos/upload-sessions?status=active",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $active->id)
        ->assertJsonPath('data.0.video_id', $video->id)
        ->assertJsonPath('data.0.upload_status_key', 'processing')
        ->assertJsonPath('data.0.video.duration_seconds', 133)
        ->assertJsonPath('data.0.video.encoding_status_key', 'processing')
        ->assertJsonPath('data.0.video.lifecycle_status_key', 'processing');
});

it('shows upload session status in center scope', function (): void {
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => VideoUploadStatus::Failed,
        'error_message' => 'Upload failed.',
    ]);

    Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadStatus::Failed,
        'lifecycle_status' => VideoLifecycleStatus::Pending,
    ]);

    $show = $this->actingAs($admin, 'admin')->getJson(
        "/api/v1/admin/centers/{$center->id}/videos/upload-sessions/{$session->id}",
        $this->adminHeaders()
    );

    $show->assertOk()
        ->assertJsonPath('data.id', $session->id)
        ->assertJsonPath('data.upload_status_key', 'failed')
        ->assertJsonPath('data.last_error_message', 'Upload failed.');

    $strangerSession = VideoUploadSession::factory()->create([
        'center_id' => $otherCenter->id,
        'uploaded_by' => User::factory()->create(['center_id' => $otherCenter->id]),
    ]);

    $forbidden = $this->actingAs($admin, 'admin')->getJson(
        "/api/v1/admin/centers/{$center->id}/videos/upload-sessions/{$strangerSession->id}",
        $this->adminHeaders()
    );

    $forbidden->assertStatus(404);
});
