<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Helpers\ApiTestHelper;
use Tests\Helpers\EnrollmentTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class, EnrollmentTestHelper::class)
    ->group('playback', 'bunny');

beforeEach(function (): void {
    config([
        'services.system_api_key' => 'system-key',
        'bunny.api.library_id' => 55,
        'bunny.embed_key' => 'embed-secret',
        'bunny.embed_token_ttl' => 240,
    ]);
});

test('request playback returns embed url and enforces 80 percent full play', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    [$student, $center, $course, $video] = buildRequestPlaybackContext();
    $this->asApiUser($student);
    $this->enrollStudent($student, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/request_playback");

    $response->assertOk()
        ->assertJsonPath('data.session_id', fn ($value) => is_int($value) && $value > 0)
        ->assertJsonPath('data.embed_url', fn ($value) => is_string($value) && str_contains($value, 'iframe.mediadelivery.net'))
        ->assertJsonPath('data.session_expires_in', fn ($value) => is_int($value) && $value > 0)
        ->assertJsonPath('data.session_expires_at', fn ($value) => is_string($value) && ! empty($value))
        ->assertJsonPath('data.embed_token_expires', fn ($value) => is_int($value) && $value > 0);

    $payload = $response->json('data');

    // Verify embed URL format contains expected components
    $embedUrl = $payload['embed_url'];
    expect($embedUrl)->toContain('iframe.mediadelivery.net/embed/55/video-uuid')
        ->toContain('token=')
        ->toContain('expires=');

    $expectedEmbedExpires = Carbon::now()->addSeconds(240)->timestamp;
    expect($payload['embed_token_expires'])->toBe($expectedEmbedExpires);

    $sessionId = $payload['session_id'];
    $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => $sessionId,
        'percentage' => 79,
    ])->assertOk();

    $session = PlaybackSession::query()->find($sessionId);
    expect($session?->is_full_play)->toBeFalse();

    $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/videos/{$video->id}/playback_progress", [
        'session_id' => $sessionId,
        'percentage' => 80,
    ])->assertOk();

    $session->refresh();
    expect($session->is_full_play)->toBeTrue();

    Carbon::setTestNow();
});

/**
 * @return array{0:User,1:Center,2:Course,3:Video}
 */
function buildRequestPlaybackContext(): array
{
    $center = Center::factory()->create([
        'type' => 0,
        'default_view_limit' => 2,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $upload = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'video-uuid',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $upload->id,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    return [$student, $center, $course, $video];
}
