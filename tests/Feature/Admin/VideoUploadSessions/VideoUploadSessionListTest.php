<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('lists upload sessions for admin center', function (): void {
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();

    /** @var User $admin */
    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    /** @var VideoUploadSession $session */
    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => VideoUploadService::STATUS_FAILED,
        'progress_percent' => 60,
        'error_message' => 'Transcode failed',
    ]);

    /** @var Video $video */
    $video = Video::factory()->create([
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    VideoUploadSession::factory()->create(['center_id' => $otherCenter->id]);

    $response = $this->actingAs($admin, 'admin')->getJson('/api/v1/admin/video-upload-sessions?per_page=10');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $session->id)
        ->assertJsonPath('data.0.upload_status', VideoUploadService::STATUS_FAILED)
        ->assertJsonPath('data.0.error_message', 'Transcode failed')
        ->assertJsonPath('data.0.videos.0.id', $video->id)
        ->assertJsonPath('data.0.videos.0.encoding_status', VideoUploadService::STATUS_PROCESSING);
});

it('requires authentication', function (): void {
    $response = $this->getJson('/api/v1/admin/video-upload-sessions');

    $response->assertStatus(401);
});
