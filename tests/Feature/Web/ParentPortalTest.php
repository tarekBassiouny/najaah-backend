<?php

declare(strict_types=1);

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'parent', 'portal');

/*
|--------------------------------------------------------------------------
| 6.6 — Parent portal: linked student access, unauthorized blocked
|--------------------------------------------------------------------------
*/

test('parent can list linked students', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->getJson('/api/v1/web/students');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

test('parent can view linked student detail', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->getJson("/api/v1/web/students/{$student->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('parent cannot access unlinked student', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $this->asWebParent(center: $center);

    $unlinkedStudent = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    $response = $this->getJson("/api/v1/web/students/{$unlinkedStudent->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false);
});

test('parent cannot access student with revoked link', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Revoked,
    ]);

    $response = $this->getJson("/api/v1/web/students/{$student->id}");

    $response->assertStatus(403);
});

test('parent cannot access student with pending link', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::PendingApproval,
    ]);

    $response = $this->getJson("/api/v1/web/students/{$student->id}");

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| 6.7 — Parent link requests
|--------------------------------------------------------------------------
*/

test('parent can request link to student by phone', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'phone' => '5555555555',
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    $response = $this->postJson('/api/v1/web/links', [
        'student_phone' => '5555555555',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $link = ParentStudentLink::where('parent_user_id', $parent->id)
        ->where('student_user_id', $student->id)
        ->first();

    expect($link)->not->toBeNull();
    expect($link->status)->toBe(ParentLinkStatus::PendingApproval);
    expect($link->link_method)->toBe(ParentLinkMethod::ParentRequested);
});

test('parent can list their link requests', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->pending()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
    ]);

    $response = $this->getJson('/api/v1/web/links');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

test('parent can view student enrollments', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->create([
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->getJson("/api/v1/web/students/{$student->id}/enrollments");

    $response->assertOk()
        ->assertJsonPath('success', true);
});
