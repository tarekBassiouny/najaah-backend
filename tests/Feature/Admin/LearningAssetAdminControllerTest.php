<?php

declare(strict_types=1);

use App\Enums\AIContentSourceType;
use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'learning-assets');

it('lists learning assets for a course and source', function (): void {
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

    $asset = LearningAsset::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'asset_type' => LearningAssetType::Summary,
        'title_translations' => ['en' => 'Source summary'],
    ]);

    LearningAsset::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id + 1000,
        'asset_type' => LearningAssetType::Summary,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/courses/{$course->id}/learning-assets?attachable_type=video&attachable_id={$video->id}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $asset->id)
        ->assertJsonPath('data.0.title', 'Source summary');
});

it('publishing a learning asset archives the previous published asset for the same source and type', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $admin = $this->asCenterAdmin($center);

    $liveAsset = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'asset_type' => LearningAssetType::Flashcards,
        'created_by' => $admin->id,
        'published_by' => $admin->id,
    ]);

    $draftAsset = LearningAsset::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'asset_type' => LearningAssetType::Flashcards,
        'status' => LearningAssetStatus::Draft,
        'is_active' => false,
        'created_by' => $admin->id,
    ]);

    $response = $this->patchJson(
        "/api/v1/admin/centers/{$center->id}/learning-assets/{$draftAsset->id}/status",
        ['status' => LearningAssetStatus::Published->value],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $draftAsset->id)
        ->assertJsonPath('data.status', LearningAssetStatus::Published->value)
        ->assertJsonPath('data.is_active', true);

    $liveAsset->refresh();
    $draftAsset->refresh();

    expect($liveAsset->status)->toBe(LearningAssetStatus::Archived);
    expect($liveAsset->is_active)->toBeFalse();
    expect($draftAsset->status)->toBe(LearningAssetStatus::Published);
    expect($draftAsset->is_active)->toBeTrue();
});
