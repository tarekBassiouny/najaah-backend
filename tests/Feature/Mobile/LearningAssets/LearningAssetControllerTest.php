<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\Assignment;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'learning-assets');

it('lists only published summaries for enrolled student', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    LearningAsset::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
        'status' => LearningAssetStatus::Draft,
        'is_active' => false,
    ]);
    $published = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assets?type=summary");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id);
});

it('shows published summary detail for enrolled student', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $asset = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
        'content_translations' => ['en' => 'Summary content'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assets/summary/{$asset->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonPath('data.type', LearningAssetType::Summary->value);
});

it('denies summary detail when student is not enrolled', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $asset = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assets/summary/{$asset->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('lists mixed course assets from the canonical mobile assets endpoint', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->active()->create([
        'order_index' => 1,
    ]);
    $assignment = Assignment::factory()->forCourse($course)->active()->create([
        'order_index' => 2,
    ]);
    $summary = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assets");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $quiz->id)
        ->assertJsonPath('data.0.type', 'quiz')
        ->assertJsonPath('data.1.id', $assignment->id)
        ->assertJsonPath('data.1.type', 'assignment')
        ->assertJsonPath('data.2.id', $summary->id)
        ->assertJsonPath('data.2.type', 'summary');
});

it('tracks summary progress and exposes it in detail and list', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $asset = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
    ]);

    $this->asApiUser($student);

    $trackResponse = $this->apiPost("/api/v1/centers/{$center->id}/assets/summary/{$asset->id}/progress", [
        'progress_percent' => 100,
        'completed' => true,
    ]);

    $trackResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.is_completed', true)
        ->assertJsonPath('data.progress_percent', 100);

    $detailResponse = $this->apiGet("/api/v1/centers/{$center->id}/assets/summary/{$asset->id}");

    $detailResponse->assertOk()
        ->assertJsonPath('data.payload.progress.status', 'completed')
        ->assertJsonPath('data.payload.progress.is_completed', true)
        ->assertJsonPath('data.payload.progress.progress_percent', 100);

    $listResponse = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assets?type=summary");

    $listResponse->assertOk()
        ->assertJsonPath('data.0.meta.progress.status', 'completed')
        ->assertJsonPath('data.0.meta.progress.is_completed', true)
        ->assertJsonPath('data.0.meta.progress.progress_percent', 100);
});

it('tracks flashcards resume state', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $asset = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Flashcards,
        'payload' => [
            'cards' => [
                ['front' => 'Q1', 'back' => 'A1'],
                ['front' => 'Q2', 'back' => 'A2'],
                ['front' => 'Q3', 'back' => 'A3'],
                ['front' => 'Q4', 'back' => 'A4'],
            ],
        ],
    ]);

    $this->asApiUser($student);

    $trackResponse = $this->apiPost("/api/v1/centers/{$center->id}/assets/flashcards/{$asset->id}/progress", [
        'progress_percent' => 50,
        'state' => [
            'current_card_index' => 2,
            'cards_studied' => 2,
        ],
    ]);

    $trackResponse->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.progress_percent', 50)
        ->assertJsonPath('data.state.current_card_index', 2);

    $detailResponse = $this->apiGet("/api/v1/centers/{$center->id}/assets/flashcards/{$asset->id}");

    $detailResponse->assertOk()
        ->assertJsonPath('data.payload.progress.status', 'in_progress')
        ->assertJsonPath('data.payload.progress.progress_percent', 50)
        ->assertJsonPath('data.payload.progress.state.current_card_index', 2)
        ->assertJsonPath('data.payload.progress.state.cards_studied', 2);
});

it('rejects progress tracking for quiz assets', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/assets/quiz/{$quiz->id}/progress", [
        'progress_percent' => 25,
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNSUPPORTED_PROGRESS_TRACKING');
});
