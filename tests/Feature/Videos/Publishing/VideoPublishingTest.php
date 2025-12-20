<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

function attachVideoToCourseForPublishing(Course $course, Video $video): void
{
    CourseVideo::create([
        'course_id' => $course->id,
        'video_id' => $video->id,
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
}

it('blocks publishing when any video is not ready', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $video = Video::factory()->create([
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/publish");

    $response->assertStatus(422);
});

it('allows publishing when videos are ready and latest session ready', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 0]);
    Section::factory()->create(['course_id' => $course->id]);

    $session = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'uploaded_by' => $admin->id,
        'upload_status' => 3,
        'progress_percent' => 100,
    ]);

    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $session->id,
        'created_by' => $admin->id,
    ]);
    attachVideoToCourseForPublishing($course, $video);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.status', 3);
});
