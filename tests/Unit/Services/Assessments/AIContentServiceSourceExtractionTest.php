<?php

declare(strict_types=1);

use App\Enums\AIContentSourceType;
use App\Enums\TextExtractionStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\Video;
use App\Services\Assessments\AIContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('ai-content', 'services');

it('includes transcript and extracted pdf text for section source extraction', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);
    $section = Section::factory()->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Algebra Basics'],
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Variables'],
        'transcript' => 'Transcripted video lesson.',
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Worksheet'],
        'text_content' => 'Extracted worksheet text.',
        'text_extraction_status' => TextExtractionStatus::Completed,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
    $course->pdfs()->attach($pdf->id, [
        'section_id' => $section->id,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);

    $service = app(AIContentService::class);
    $method = (new \ReflectionClass($service))->getMethod('extractSourceContentByContext');
    $method->setAccessible(true);

    $content = $method->invoke($service, $center->id, $course->id, AIContentSourceType::Section, $section->id);

    expect($content)->toContain('Section title: Algebra Basics')
        ->toContain('Video: Variables')
        ->toContain('Transcripted video lesson.')
        ->toContain('PDF: Worksheet')
        ->toContain('Extracted worksheet text.');
});

it('includes section assets when extracting course source content', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Full Course'],
    ]);
    $section = Section::factory()->create([
        'course_id' => $course->id,
        'title_translations' => ['en' => 'Section One'],
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Lesson Video'],
        'transcript' => 'Video transcript content.',
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Lesson PDF'],
        'text_content' => 'PDF extracted content.',
        'text_extraction_status' => TextExtractionStatus::Completed,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
    $course->pdfs()->attach($pdf->id, [
        'section_id' => $section->id,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);

    $service = app(AIContentService::class);
    $method = (new \ReflectionClass($service))->getMethod('extractSourceContentByContext');
    $method->setAccessible(true);

    $content = $method->invoke($service, $center->id, $course->id, AIContentSourceType::Course, $course->id);

    expect($content)->toContain('Course title: Full Course')
        ->toContain('Section: Section One')
        ->toContain('Video transcript content.')
        ->toContain('PDF extracted content.');
});

it('includes direct course assets outside sections when extracting course source content', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Mixed Course'],
    ]);

    $video = Video::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Direct Video'],
        'transcript' => 'Direct transcript content.',
    ]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Direct PDF'],
        'text_content' => 'Direct PDF content.',
        'text_extraction_status' => TextExtractionStatus::Completed,
    ]);

    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
    $course->pdfs()->attach($pdf->id, [
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);

    $service = app(AIContentService::class);
    $method = (new \ReflectionClass($service))->getMethod('extractSourceContentByContext');
    $method->setAccessible(true);

    $content = $method->invoke($service, $center->id, $course->id, AIContentSourceType::Course, $course->id);

    expect($content)->toContain('Course title: Mixed Course')
        ->toContain('Video: Direct Video')
        ->toContain('Direct transcript content.')
        ->toContain('PDF: Direct PDF')
        ->toContain('Direct PDF content.');
});
