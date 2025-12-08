<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('courses', 'api');

beforeEach(function (): void {
    $user = $this->makeApiUser();
    $this->asApiUser($user);
});

it('lists published courses', function (): void {
    $this->createCourse(['status' => 0]);
    $published = $this->createCourse(['status' => 3]);

    $response = $this->apiGet('/api/v1/courses');
    $response->assertOk();
});

it('shows published course', function (): void {
    $course = $this->createCourse(['status' => 3]);
    $response = $this->apiGet("/api/v1/courses/{$course->id}");
    $response->assertOk();
});

it('lists videos for published course', function (): void {
    $course = $this->createCourseWithVideos();
    $this->publishCourse($course);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/videos");
    $response->assertOk();
});

it('shows single video metadata', function (): void {
    $course = $this->createCourseWithVideos();
    $this->publishCourse($course);
    $video = $course->videos->first();

    $response = $this->apiGet("/api/v1/courses/{$course->id}/videos/{$video?->id}");
    $response->assertOk();
});

it('lists pdfs for published course', function (): void {
    $course = $this->createCourseWithPdfs();
    $this->publishCourse($course);

    $response = $this->apiGet("/api/v1/courses/{$course->id}/pdfs");
    $response->assertOk();
});

it('shows single pdf metadata', function (): void {
    $course = $this->createCourseWithPdfs();
    $this->publishCourse($course);
    $pdf = $course->pdfs->first();

    $response = $this->apiGet("/api/v1/courses/{$course->id}/pdfs/{$pdf?->id}");
    $response->assertOk();
});
