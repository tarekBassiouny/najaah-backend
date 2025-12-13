<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('creates bunny video and returns upload url', function (): void {
    $center = Center::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create(['is_student' => false]);

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'original_filename' => 'sample.mp4',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.upload_status', VideoUploadService::STATUS_PENDING)
        ->assertJsonPath('data.bunny_upload_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('data.upload_url', fn ($url) => is_string($url) && $url !== '');

    $this->assertDatabaseHas('video_upload_sessions', [
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
    ]);
});

it('ties existing video to new upload session', function (): void {
    $center = Center::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->create(['is_student' => false]);
    /** @var Video $video */
    $video = Video::factory()->create([
        'created_by' => $admin->id,
        'encoding_status' => 0,
        'lifecycle_status' => 0,
        'upload_session_id' => null,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/video-uploads', [
        'center_id' => $center->id,
        'video_id' => $video->id,
        'original_filename' => 'sample.mp4',
    ]);

    $response->assertCreated();

    $video->refresh();
    expect($video->upload_session_id)->not->toBeNull()
        ->and($video->source_id)->not->toBeNull()
        ->and($video->encoding_status)->toBe(VideoUploadService::STATUS_PENDING);
});
