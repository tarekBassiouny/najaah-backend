<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Video;
use App\Services\Playback\PlaybackAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;

uses(RefreshDatabase::class)->group('playback', 'api');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function mockAuthorizationFailure(string $code, string $message): void
{
    $mock = Mockery::mock(PlaybackAuthorizationService::class);
    $mock->shouldReceive('authorize')
        ->andThrow(new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 403)));

    app()->instance(PlaybackAuthorizationService::class, $mock);
}

it('rejects unauthenticated playback authorization', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->postJson("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

it('authorizes playback when all rules pass', function (): void {
    $course = Course::factory()->create();
    $video = Video::factory()->create([
        'source_id' => 'bunny-video-1',
        'library_id' => 12,
    ]);

    $this->mock(PlaybackAuthorizationService::class)
        ->shouldReceive('authorize')
        ->andReturn([
            'embed_config' => [
                'video_id' => 'bunny-video-1',
                'library_id' => 12,
            ],
        ]);

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.embed_config.video_id', 'bunny-video-1')
        ->assertJsonPath('data.embed_config.library_id', 12)
        ->assertJsonMissing(['playback_url', 'expires_at', 'api_key', 'token', 'signed_url', 'cdn_url', 'upload_url']);
});

it('blocks playback when not enrolled', function (): void {
    mockAuthorizationFailure('ENROLLMENT_REQUIRED', 'Active enrollment is required for playback.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'ENROLLMENT_REQUIRED');
});

it('blocks playback when center does not match', function (): void {
    mockAuthorizationFailure('CENTER_MISMATCH', 'User does not belong to this center.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('blocks playback when video is not ready', function (): void {
    mockAuthorizationFailure('VIDEO_NOT_READY', 'Video is not ready for playback.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'VIDEO_NOT_READY');
});

it('blocks playback for invalid device', function (): void {
    mockAuthorizationFailure('DEVICE_MISMATCH', 'Device is not authorized for this user.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'DEVICE_MISMATCH');
});

it('blocks concurrent playback', function (): void {
    mockAuthorizationFailure('CONCURRENT_PLAYBACK', 'Another playback session is active.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'CONCURRENT_PLAYBACK');
});

it('blocks playback when view limit exceeded', function (): void {
    mockAuthorizationFailure('VIEW_LIMIT_EXCEEDED', 'View limit exceeded.');

    $course = Course::factory()->create();
    $video = Video::factory()->create();

    $response = $this->apiPost("/api/v1/courses/{$course->id}/videos/{$video->id}/playback/authorize", [
        'device_id' => 'device-123',
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'VIEW_LIMIT_EXCEEDED');
});
