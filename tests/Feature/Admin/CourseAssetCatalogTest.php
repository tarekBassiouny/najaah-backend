<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\AIContentJob;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'courses');

it('returns source-bound asset catalog for course builder', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create([
        'course_id' => $course->id,
        'order_index' => 1,
        'title_translations' => ['en' => 'Introduction'],
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Welcome video'],
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => $section->id,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $admin = $this->asCenterAdmin($center);

    $summary = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'asset_type' => LearningAssetType::Summary,
        'title_translations' => ['en' => 'Welcome summary'],
        'created_by' => $admin->id,
        'published_by' => $admin->id,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->active()->create([
        'center_id' => $center->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'title_translations' => ['en' => 'Welcome quiz'],
        'created_by' => $admin->id,
    ]);

    $job = AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'batch_key' => '11111111-1111-1111-1111-111111111111',
        'source_type' => AIContentSourceType::Video,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Flashcards,
        'status' => AIContentJobStatus::Processing,
        'created_by' => $admin->id,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/asset-catalog?source_type=video&source_id={$video->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.course.id', $course->id)
        ->assertJsonCount(1, 'data.sources')
        ->assertJsonPath('data.sources.0.title', 'Welcome video')
        ->assertJsonPath('data.sources.0.section.title', 'Introduction');

    $source = $response->json('data.sources.0');
    $assets = collect($source['assets'])->keyBy('asset_type');

    expect($assets['summary']['slot_state'])->toBe('published');
    expect($assets['summary']['canonical']['id'])->toBe($summary->id);
    expect($assets['quiz']['slot_state'])->toBe('published');
    expect($assets['quiz']['canonical']['id'])->toBe($quiz->id);
    expect($assets['flashcards']['slot_state'])->toBe('generating');
    expect($assets['flashcards']['latest_job']['id'])->toBe($job->id);
    expect($assets['assignment']['slot_state'])->toBe('missing');
});

it('shows draft when only inactive canonical asset exists', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);

    LearningAsset::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'asset_type' => LearningAssetType::Flashcards,
        'status' => LearningAssetStatus::Draft,
        'is_active' => false,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/asset-catalog?source_type=video&source_id={$video->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.sources.0.assets.2.asset_type', 'flashcards')
        ->assertJsonPath('data.sources.0.assets.2.slot_state', 'draft');
});
