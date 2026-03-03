<?php

declare(strict_types=1);

use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'playback-sessions');

it('returns center-scoped playback sessions with metadata', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $course = Course::factory()->create(['center_id' => $center->id]);

    $video = Video::factory()
        ->for($center, 'center')
        ->create([
            'title_translations' => ['en' => 'Physics 101'],
            'encoding_status' => VideoUploadStatus::Ready,
            'lifecycle_status' => VideoLifecycleStatus::Ready,
            'created_by' => $admin->id,
        ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'name' => 'Physics Student',
    ]);

    $device = UserDevice::factory()->create(['user_id' => $student->id]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'is_locked' => false,
        'auto_closed' => false,
        'progress_percent' => 85,
        'watch_duration' => 180,
        'started_at' => now()->subMinutes(5),
        'last_activity_at' => now(),
    ]);

    $otherCenter = Center::factory()->create();
    $otherCourse = Course::factory()->create(['center_id' => $otherCenter->id]);
    $otherVideo = Video::factory()
        ->for($otherCenter, 'center')
        ->create([
            'title_translations' => ['en' => 'Other Course'],
            'encoding_status' => VideoUploadStatus::Ready,
            'lifecycle_status' => VideoLifecycleStatus::Ready,
        ]);
    $otherStudent = User::factory()->create([
        'center_id' => $otherCenter->id,
        'is_student' => true,
    ]);
    $otherDevice = UserDevice::factory()->create(['user_id' => $otherStudent->id]);

    PlaybackSession::factory()->create([
        'user_id' => $otherStudent->id,
        'video_id' => $otherVideo->id,
        'course_id' => $otherCourse->id,
        'device_id' => $otherDevice->id,
        'is_full_play' => false,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/playback-sessions");

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.video.title', 'Physics 101')
        ->assertJsonPath('data.0.user.name', 'Physics Student')
        ->assertJsonPath('data.0.is_full_play', true)
        ->assertJsonPath('data.0.device.status_label', 'Active');
});

it('supports search and boolean filters', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()
        ->for($center, 'center')
        ->create([
            'title_translations' => ['en' => 'Searchable Video'],
            'encoding_status' => VideoUploadStatus::Ready,
            'lifecycle_status' => VideoLifecycleStatus::Ready,
        ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'name' => 'Search Hero',
    ]);

    $device = UserDevice::factory()->create(['user_id' => $student->id]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'is_full_play' => true,
        'is_locked' => true,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/playback-sessions?search=Search&is_full_play=true");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.video.title', 'Searchable Video')
        ->assertJsonPath('data.0.is_locked', true);
});
