<?php

declare(strict_types=1);

use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class)->group('commands', 'playback');

test('it closes stale sessions with default timeout', function (): void {
    Carbon::setTestNow('2024-01-01 12:00:00');

    $user = User::factory()->create();
    $device = UserDevice::factory()->create(['user_id' => $user->id, 'status' => UserDevice::STATUS_ACTIVE]);
    $video = Video::factory()->create();

    $staleSession = PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => null,
        'last_activity_at' => Carbon::parse('2024-01-01 11:58:00'),
    ]);

    $activeSession = PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => null,
        'last_activity_at' => Carbon::parse('2024-01-01 11:59:30'),
    ]);

    $this->artisan('playback:close-stale')
        ->assertSuccessful()
        ->expectsOutputToContain('Closed 1 stale sessions');

    $staleSession->refresh();
    $activeSession->refresh();

    expect($staleSession->ended_at)->not->toBeNull()
        ->and($staleSession->close_reason)->toBe('timeout')
        ->and($staleSession->auto_closed)->toBeTrue()
        ->and($activeSession->ended_at)->toBeNull();

    Carbon::setTestNow();
});

test('it uses custom timeout option', function (): void {
    Carbon::setTestNow('2024-01-01 12:00:00');

    $user = User::factory()->create();
    $device = UserDevice::factory()->create(['user_id' => $user->id, 'status' => UserDevice::STATUS_ACTIVE]);
    $video = Video::factory()->create();

    $session = PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => null,
        'last_activity_at' => Carbon::parse('2024-01-01 11:58:00'),
    ]);

    $this->artisan('playback:close-stale', ['--timeout' => 180])
        ->assertSuccessful()
        ->expectsOutputToContain('Closed 0 stale sessions');

    $session->refresh();
    expect($session->ended_at)->toBeNull();

    Carbon::setTestNow();
});

test('it ignores already closed sessions', function (): void {
    Carbon::setTestNow('2024-01-01 12:00:00');

    $user = User::factory()->create();
    $device = UserDevice::factory()->create(['user_id' => $user->id, 'status' => UserDevice::STATUS_ACTIVE]);
    $video = Video::factory()->create();

    PlaybackSession::factory()->create([
        'user_id' => $user->id,
        'video_id' => $video->id,
        'device_id' => $device->id,
        'ended_at' => Carbon::parse('2024-01-01 11:55:00'),
        'last_activity_at' => Carbon::parse('2024-01-01 11:50:00'),
    ]);

    $this->artisan('playback:close-stale')
        ->assertSuccessful()
        ->expectsOutputToContain('Closed 0 stale sessions');

    Carbon::setTestNow();
});
