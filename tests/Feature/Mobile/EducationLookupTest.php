<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\College;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('education', 'mobile');

it('returns only active grade lookup items for mobile', function (): void {
    $center = Center::factory()->create(['api_key' => 'education-center-key']);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
            ],
        ],
    ]);

    Grade::factory()->create(['center_id' => $center->id, 'is_active' => true]);
    Grade::factory()->create(['center_id' => $center->id, 'is_active' => false]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/grades", ['X-Api-Key' => $center->api_key]);

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('returns empty mobile lookup when module is disabled', function (): void {
    $center = Center::factory()->create(['api_key' => 'education-center-disabled-key']);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => false,
                'enable_school' => true,
                'enable_college' => true,
            ],
        ],
    ]);

    Grade::factory()->create(['center_id' => $center->id, 'is_active' => true]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/grades", ['X-Api-Key' => $center->api_key]);

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('returns 422 when disabled module field is submitted in education update', function (): void {
    $center = Center::factory()->create(['api_key' => 'education-update-disabled-key']);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => false,
                'enable_school' => true,
                'enable_college' => true,
            ],
        ],
    ]);

    $grade = Grade::factory()->create(['center_id' => $center->id, 'is_active' => true]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);

    $response = $this->apiPatch('/api/v1/auth/me/education', [
        'grade_id' => $grade->id,
    ], ['X-Api-Key' => $center->api_key]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('returns 422 when disabled module field is submitted as null in education update', function (): void {
    $center = Center::factory()->create(['api_key' => 'education-update-disabled-null-key']);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => false,
                'enable_school' => true,
                'enable_college' => true,
            ],
        ],
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);

    $response = $this->apiPatch('/api/v1/auth/me/education', [
        'grade_id' => null,
    ], ['X-Api-Key' => $center->api_key]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('updates student education on mobile endpoint when allowed', function (): void {
    $center = Center::factory()->create(['api_key' => 'education-update-ok-key']);
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
                'require_grade' => true,
                'require_school' => false,
                'require_college' => false,
            ],
        ],
    ]);

    $grade = Grade::factory()->create(['center_id' => $center->id, 'is_active' => true]);
    $school = School::factory()->create(['center_id' => $center->id, 'is_active' => true]);
    $college = College::factory()->create(['center_id' => $center->id, 'is_active' => true]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);
    Cache::put('student_profile:'.$student->id, ['stale' => true], 300);

    $response = $this->apiPatch('/api/v1/auth/me/education', [
        'grade_id' => $grade->id,
        'school_id' => $school->id,
        'college_id' => $college->id,
    ], ['X-Api-Key' => $center->api_key]);

    $response->assertOk()
        ->assertJsonPath('data.grade.id', $grade->id)
        ->assertJsonPath('data.school.id', $school->id)
        ->assertJsonPath('data.college.id', $college->id);

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'grade_id' => $grade->id,
        'school_id' => $school->id,
        'college_id' => $college->id,
    ]);
    expect(Cache::get('student_profile:'.$student->id))->toBeNull();
});

it('returns 422 when submitted education fields belong to different centers', function (): void {
    config(['services.system_api_key' => 'system-key']);

    $centerA = Center::factory()->create(['api_key' => 'education-mixed-center-a-key', 'type' => 0]);
    $centerB = Center::factory()->create(['api_key' => 'education-mixed-center-b-key', 'type' => 0]);

    CenterSetting::factory()->create([
        'center_id' => $centerA->id,
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
            ],
        ],
    ]);

    $grade = Grade::factory()->create(['center_id' => $centerA->id, 'is_active' => true]);
    $school = School::factory()->create(['center_id' => $centerB->id, 'is_active' => true]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'password' => 'secret123',
    ]);

    $this->asApiUser($student);

    $response = $this->apiPatch('/api/v1/auth/me/education', [
        'grade_id' => $grade->id,
        'school_id' => $school->id,
    ], ['X-Api-Key' => 'system-key']);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});
