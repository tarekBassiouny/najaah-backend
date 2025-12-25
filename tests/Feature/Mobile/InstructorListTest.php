<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'instructors');

it('lists instructors for branded student center', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $centerInstructor = Instructor::factory()->create([
        'center_id' => $centerA->id,
        'name_translations' => ['en' => 'Center Instructor'],
    ]);
    Instructor::factory()->create([
        'center_id' => $centerB->id,
        'name_translations' => ['en' => 'Other Instructor'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/instructors');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $centerInstructor->id);
});

it('lists only unbranded instructors for system students', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $systemInstructor = Instructor::factory()->create([
        'center_id' => $unbranded->id,
        'name_translations' => ['en' => 'System Instructor'],
    ]);
    Instructor::factory()->create([
        'center_id' => $branded->id,
        'name_translations' => ['en' => 'Branded Instructor'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/instructors');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $systemInstructor->id);
});

it('searches instructors by name or title', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Professor Alpha'],
        'title_translations' => ['en' => 'Lecturer'],
    ]);
    Instructor::factory()->create([
        'center_id' => $center->id,
        'name_translations' => ['en' => 'Other'],
        'title_translations' => ['en' => 'Assistant'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/instructors?search=Alpha');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('paginates instructor list', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Instructor::factory()->count(2)->create([
        'center_id' => $center->id,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/instructors?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});
