<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('courses', 'mobile', 'show');

it('shows course aggregate with metadata only', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $section = Section::factory()->create([
        'course_id' => $course->id,
        'order_index' => 1,
    ]);

    $readySession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);

    $video = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'video-uuid',
        'duration_seconds' => 120,
        'thumbnail_url' => 'https://cdn.example.com/thumb-1.jpg',
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $readySession->id,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'section_id' => $section->id,
        'video_id' => $video->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    $pdf = Pdf::factory()->create();

    CoursePdf::create([
        'course_id' => $course->id,
        'section_id' => $section->id,
        'pdf_id' => $pdf->id,
        'order_index' => 1,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $course->id)
        ->assertJsonPath('data.is_enrolled', true)
        ->assertJsonPath('data.sections.0.id', $section->id)
        ->assertJsonPath('data.videos.0.id', $video->id)
        ->assertJsonPath('data.videos.0.duration', 120)
        ->assertJsonPath('data.videos.0.duration_seconds', 120)
        ->assertJsonPath('data.videos.0.thumbnail_url', 'https://cdn.example.com/thumb-1.jpg')
        ->assertJsonPath('data.videos.0.is_locked', false)
        ->assertJsonPath('data.pdfs.0.is_locked', false);

    $response->assertJsonMissing([
        'playback_url',
        'signed_url',
        'upload_url',
        'api_key',
        'token',
        'cdn_url',
        'download_url',
        'source_url',
        'video_uuid',
        'library_id',
    ]);
});

it('allows system students to view unbranded center courses', function (): void {
    $center = Center::factory()->create(['type' => 0]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $course->id);
});

it('marks course as not enrolled when student is not enrolled', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonPath('data.is_enrolled', false);
});

it('returns not found for unpublished courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 0,
        'is_published' => false,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('denies branded student access to another center course', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $centerB->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$centerB->id}/courses/{$course->id}");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('denies system students access to branded center courses', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('filters out non-ready videos from course details', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $section = Section::factory()->create([
        'course_id' => $course->id,
        'order_index' => 1,
    ]);

    $readySession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 3,
    ]);
    $pendingSession = VideoUploadSession::factory()->create([
        'center_id' => $center->id,
        'upload_status' => 2,
    ]);

    $readyVideo = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'ready-uuid',
        'duration_seconds' => 120,
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'upload_session_id' => $readySession->id,
    ]);

    $pendingVideo = Video::factory()->create([
        'library_id' => 55,
        'source_id' => 'pending-uuid',
        'duration_seconds' => 120,
        'encoding_status' => 2,
        'lifecycle_status' => 1,
        'upload_session_id' => $pendingSession->id,
    ]);

    CourseVideo::create([
        'course_id' => $course->id,
        'section_id' => $section->id,
        'video_id' => $readyVideo->id,
        'order_index' => 1,
        'visible' => true,
    ]);
    CourseVideo::create([
        'course_id' => $course->id,
        'section_id' => $section->id,
        'video_id' => $pendingVideo->id,
        'order_index' => 2,
        'visible' => true,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonPath('data.videos.0.id', $readyVideo->id)
        ->assertJsonCount(1, 'data.videos');
});
