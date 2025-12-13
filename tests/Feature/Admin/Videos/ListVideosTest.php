<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('lists videos with upload sessions for admin center', function (): void {
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
        'error_message' => 'Encoding failed',
    ]);

    /** @var Video $video */
    $video = Video::factory()->create([
        'created_by' => $admin->id,
        'upload_session_id' => $session->id,
        'encoding_status' => VideoUploadService::STATUS_PROCESSING,
        'lifecycle_status' => 1,
    ]);

    /** @var User $otherAdmin */
    $otherAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $otherCenter->id,
    ]);

    VideoUploadSession::factory()->create(['center_id' => $otherCenter->id]);
    Video::factory()->create([
        'created_by' => $otherAdmin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson('/api/v1/admin/videos?per_page=10');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $video->id)
        ->assertJsonPath('data.0.encoding_status', VideoUploadService::STATUS_PROCESSING)
        ->assertJsonPath('data.0.lifecycle_status', 1)
        ->assertJsonPath('data.0.upload_sessions.0.id', $session->id)
        ->assertJsonPath('data.0.upload_sessions.0.upload_status', VideoUploadService::STATUS_FAILED)
        ->assertJsonPath('data.0.upload_sessions.0.error_message', 'Encoding failed');

    $json = $response->json();
    expect($json['data'][0])->not->toHaveKey('playback_url')
        ->and($json['data'][0])->not->toHaveKey('source_url');
});

it('requires admin authentication', function (): void {
    $response = $this->getJson('/api/v1/admin/videos');

    $response->assertStatus(401);
});
