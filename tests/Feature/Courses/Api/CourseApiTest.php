<?php

declare(strict_types=1);

use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('courses', 'api');

beforeEach(function (): void {
    $user = $this->makeApiUser();
    $this->asApiUser($user);
});

it('lists published courses', function (): void {
    $draft = $this->createCourse(['status' => 0]);
    $published = $this->createCourse(['status' => 3]);
    $this->enrollStudent($this->apiUser, $draft, Enrollment::STATUS_ACTIVE);
    $this->enrollStudent($this->apiUser, $published, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet('/api/v1/courses');
    $response->assertOk()->assertJsonPath('data.0.id', $published->id);
});

it('shows published course', function (): void {
    $course = $this->createCourse(['status' => 3]);
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_ACTIVE);
    $response = $this->apiGet("/api/v1/courses/{$course->id}");
    $response->assertOk();
});

it('lists videos for published course', function (): void {
    $course = $this->createCourseWithVideos();
    $this->publishCourse($course);
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/videos");
    $response->assertOk();
});

it('shows single video metadata', function (): void {
    $course = $this->createCourseWithVideos();
    $this->publishCourse($course);
    $video = $course->videos->first();
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/videos/{$video?->id}");
    $response->assertOk();
});

it('lists pdfs for published course', function (): void {
    $course = $this->createCourseWithPdfs();
    $this->publishCourse($course);
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/pdfs");
    $response->assertOk();
});

it('shows single pdf metadata', function (): void {
    $course = $this->createCourseWithPdfs();
    $this->publishCourse($course);
    $pdf = $course->pdfs->first();
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/pdfs/{$pdf?->id}");
    $response->assertOk();
});

it('denies access without enrollment', function (): void {
    $course = $this->createCourse(['status' => 3]);

    $response = $this->apiGet("/api/v1/courses/{$course->id}");

    $response->assertForbidden();
});

it('denies access when enrollment inactive', function (): void {
    $course = $this->createCourse(['status' => 3]);
    $this->enrollStudent($this->apiUser, $course, Enrollment::STATUS_DEACTIVATED);

    $response = $this->apiGet("/api/v1/courses/{$course->id}");

    $response->assertForbidden();
});
