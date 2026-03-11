<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
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

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/summaries");

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

    $response = $this->apiGet("/api/v1/centers/{$center->id}/summaries/{$asset->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $asset->id)
        ->assertJsonPath('data.asset_type', LearningAssetType::Summary->value);
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

    $response = $this->apiGet("/api/v1/centers/{$center->id}/summaries/{$asset->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});
