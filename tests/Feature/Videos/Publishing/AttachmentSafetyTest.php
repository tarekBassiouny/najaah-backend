<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos');

it('blocks attaching non-ready video to course', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['created_by' => $admin->id]);
    $video = Video::factory()->create([
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ]);

    $response->assertStatus(422);
});

it('blocks attaching non-ready video to section', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'encoding_status' => 1,
        'lifecycle_status' => 1,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/sections/{$section->id}/videos", [
        'video_id' => $video->id,
    ]);

    $response->assertStatus(422);
});

it('allows attaching ready video', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create(['created_by' => $admin->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);
    $video = Video::factory()->create([
        'encoding_status' => 3,
        'lifecycle_status' => 2,
        'created_by' => $admin->id,
    ]);

    $courseAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/videos", [
        'video_id' => $video->id,
    ]);
    $courseAttach->assertCreated();

    $sectionAttach = $this->actingAs($admin, 'admin')->postJson("/api/v1/admin/courses/{$course->id}/sections/{$section->id}/videos", [
        'video_id' => $video->id,
    ]);
    $sectionAttach->assertCreated();
});
