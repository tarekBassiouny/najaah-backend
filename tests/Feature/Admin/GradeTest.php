<?php

declare(strict_types=1);

use App\Enums\EducationalStage;
use App\Models\Center;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('education', 'admin');

it('manages center grades and lookup', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $create = $this->postJson("/api/v1/admin/centers/{$center->id}/grades", [
        'name_translations' => ['en' => 'Grade 9', 'ar' => 'الصف التاسع'],
        'stage' => EducationalStage::HighSchool->value,
        'order' => 9,
        'is_active' => true,
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.name', 'Grade 9')
        ->assertJsonPath('data.stage', EducationalStage::HighSchool->value);

    $gradeId = (int) $create->json('data.id');

    $list = $this->getJson("/api/v1/admin/centers/{$center->id}/grades?stage=2", $this->adminHeaders());
    $list->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $gradeId);

    $lookup = $this->getJson("/api/v1/admin/centers/{$center->id}/grades/lookup", $this->adminHeaders());
    $lookup->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $gradeId);

    $update = $this->putJson("/api/v1/admin/centers/{$center->id}/grades/{$gradeId}", [
        'is_active' => false,
    ], $this->adminHeaders());

    $update->assertOk()
        ->assertJsonPath('data.is_active', false);

    $delete = $this->deleteJson("/api/v1/admin/centers/{$center->id}/grades/{$gradeId}", [], $this->adminHeaders());
    $delete->assertOk()->assertJsonPath('data', null);
});

it('prevents deleting grade assigned to students', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $grade = Grade::factory()->create(['center_id' => $center->id]);
    User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'grade_id' => $grade->id,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/grades/{$grade->id}", [], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'GRADE_HAS_STUDENTS');
});
