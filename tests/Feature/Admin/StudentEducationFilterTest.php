<?php

declare(strict_types=1);

use App\Enums\EducationalStage;
use App\Models\Center;
use App\Models\College;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('education', 'admin', 'students');

it('filters center students by education fields and stage', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $gradeA = Grade::factory()->create([
        'center_id' => $center->id,
        'stage' => EducationalStage::HighSchool->value,
    ]);
    $gradeB = Grade::factory()->create([
        'center_id' => $center->id,
        'stage' => EducationalStage::University->value,
    ]);

    $school = School::factory()->create(['center_id' => $center->id]);
    $college = College::factory()->create(['center_id' => $center->id]);

    User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'grade_id' => $gradeA->id,
        'school_id' => $school->id,
        'college_id' => $college->id,
    ]);

    User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'grade_id' => $gradeB->id,
    ]);

    $byGrade = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/students?grade_id={$gradeA->id}",
        $this->adminHeaders()
    );

    $byGrade->assertOk()->assertJsonCount(1, 'data');

    $byStage = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/students?stage=".EducationalStage::University->value,
        $this->adminHeaders()
    );

    $byStage->assertOk()->assertJsonCount(1, 'data');
});
