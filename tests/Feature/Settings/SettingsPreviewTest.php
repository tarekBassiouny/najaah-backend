<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\CourseSetting;
use App\Models\StudentSetting;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('settings', 'admin');

beforeEach(function (): void {
    $this->asAdmin();
});

it('resolves settings in correct priority order', function (): void {
    $center = Center::factory()->create([
        'default_view_limit' => 1,
        'allow_extra_view_requests' => false,
        'pdf_download_permission' => false,
        'device_limit' => 1,
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#111111',
    ]);

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
            'device_limit' => 2,
            'branding' => ['primary_color' => '#222222'],
        ],
    ]);

    $course = Course::factory()->create(['center_id' => $center->id]);
    CourseSetting::factory()->create([
        'course_id' => $course->id,
        'settings' => [
            'view_limit' => 3,
            'pdf_download_permission' => true,
        ],
    ]);

    $video = Video::factory()->create();
    $video->courses()->attach($course->id);

    VideoSetting::factory()->create([
        'video_id' => $video->id,
        'settings' => [
            'view_limit' => 4,
            'allow_extra_view_requests' => false,
        ],
    ]);

    /** @var User $student */
    $student = User::factory()->create(['is_student' => true]);
    StudentSetting::factory()->create([
        'user_id' => $student->id,
        'settings' => [
            'view_limit' => 5,
            'allow_extra_view_requests' => true,
        ],
    ]);

    $response = $this->getJson('/api/v1/admin/settings/preview?student_id='.$student->id.'&video_id='.$video->id.'&course_id='.$course->id.'&center_id='.$center->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.view_limit', 5)
        ->assertJsonPath('data.allow_extra_view_requests', true)
        ->assertJsonPath('data.pdf_download_permission', true)
        ->assertJsonPath('data.device_limit', 2)
        ->assertJsonPath('data.branding.primary_color', '#222222');
});

it('falls back when higher-level settings are missing', function (): void {
    $center = Center::factory()->create([
        'default_view_limit' => 6,
        'allow_extra_view_requests' => false,
        'pdf_download_permission' => false,
        'device_limit' => 1,
        'logo_url' => null,
        'primary_color' => null,
    ]);

    $course = Course::factory()->create(['center_id' => $center->id]);
    CourseSetting::factory()->create([
        'course_id' => $course->id,
        'settings' => [
            'pdf_download_permission' => true,
        ],
    ]);

    $video = Video::factory()->create();
    $video->courses()->attach($course->id);

    $response = $this->getJson('/api/v1/admin/settings/preview?video_id='.$video->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.view_limit', 6)
        ->assertJsonPath('data.pdf_download_permission', true)
        ->assertJsonPath('data.allow_extra_view_requests', false)
        ->assertJsonPath('data.device_limit', 1);
});

it('ignores unsupported keys', function (): void {
    $center = Center::factory()->create();

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'unknown_key' => 10,
        ],
    ]);

    $response = $this->getJson('/api/v1/admin/settings/preview?center_id='.$center->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonMissing(['data' => ['unknown_key' => 10]])
        ->assertJsonPath('data.view_limit', 2);
});

it('requires authentication', function (): void {
    $center = Center::factory()->create();

    auth('admin')->logout();

    $response = $this->getJson('/api/v1/admin/settings/preview?center_id='.$center->id);

    $response->assertStatus(401);
});
