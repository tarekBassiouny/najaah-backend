<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Playback\ViewLimitService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class)->group('playback', 'services');

test('getRemainingViews returns null for unlimited videos', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 0]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create();
    $user = User::factory()->create(['center_id' => $center->id]);

    $service = app(ViewLimitService::class);
    $remaining = $service->getRemainingViews($user, $video, $course);

    expect($remaining)->toBeNull();
});

test('getRemainingViews returns correct count with view limit', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 5]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $upload = VideoUploadSession::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['upload_session_id' => $upload->id]);
    $user = User::factory()->create(['center_id' => $center->id]);
    $device = UserDevice::factory()->create(['user_id' => $user->id, 'status' => UserDevice::STATUS_ACTIVE]);

    PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'ended_at' => now(),
    ]);

    PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'ended_at' => now(),
    ]);

    $service = app(ViewLimitService::class);
    $remaining = $service->getRemainingViews($user, $video, $course);

    expect($remaining)->toBe(3);
});

test('getEffectiveLimit returns null for unlimited', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 0]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create();
    $user = User::factory()->create(['center_id' => $center->id]);

    $service = app(ViewLimitService::class);
    $limit = $service->getEffectiveLimit($user, $video, $course);

    expect($limit)->toBeNull();
});

test('getEffectiveLimit returns limit value', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 10]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create();
    $user = User::factory()->create(['center_id' => $center->id]);

    $service = app(ViewLimitService::class);
    $limit = $service->getEffectiveLimit($user, $video, $course);

    expect($limit)->toBe(10);
});

test('isLocked returns false when views remain', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 5]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create();
    $user = User::factory()->create(['center_id' => $center->id]);

    $service = app(ViewLimitService::class);
    $locked = $service->isLocked($user, $video, $course);

    expect($locked)->toBeFalse();
});

test('isLocked returns true when views exhausted', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 1]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $upload = VideoUploadSession::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['upload_session_id' => $upload->id]);
    $user = User::factory()->create(['center_id' => $center->id]);
    $device = UserDevice::factory()->create(['user_id' => $user->id, 'status' => UserDevice::STATUS_ACTIVE]);

    PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'ended_at' => now(),
    ]);

    $service = app(ViewLimitService::class);
    $locked = $service->isLocked($user, $video, $course);

    expect($locked)->toBeTrue();
});

test('isLocked returns false for unlimited videos', function (): void {
    $center = Center::factory()->create(['default_view_limit' => 0]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create();
    $user = User::factory()->create(['center_id' => $center->id]);

    $service = app(ViewLimitService::class);
    $locked = $service->isLocked($user, $video, $course);

    expect($locked)->toBeFalse();
});
