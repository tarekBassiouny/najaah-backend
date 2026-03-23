<?php

declare(strict_types=1);

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Models\Center;
use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'admin', 'parent-management');

/*
|--------------------------------------------------------------------------
| 6.11 — Admin parent management: create link, approve, reject, revoke
|--------------------------------------------------------------------------
*/

test('admin can list parents for a center', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    User::factory()->count(3)->create([
        'center_id' => $center->id,
        'is_parent' => true,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/parents");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

test('admin can search parents by name', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    User::factory()->create([
        'center_id' => $center->id,
        'is_parent' => true,
        'name' => 'Ahmed Hassan',
    ]);

    User::factory()->create([
        'center_id' => $center->id,
        'is_parent' => true,
        'name' => 'Sara Mohamed',
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/parents?search=Ahmed");

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('admin can view parent detail with links', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $parent = User::factory()->create([
        'center_id' => $center->id,
        'is_parent' => true,
    ]);

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

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/parents/{$parent->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.parent.id', $parent->id)
        ->assertJsonCount(1, 'data.links');
});

test('admin can create parent-student link', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);

    $parent = User::factory()->create([
        'center_id' => $center->id,
        'is_parent' => true,
    ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/parent-links", [
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'Active');

    $link = ParentStudentLink::where('parent_user_id', $parent->id)->first();
    expect($link->status)->toBe(ParentLinkStatus::Active);
    expect($link->link_method)->toBe(ParentLinkMethod::AdminManaged);
    expect($link->linked_by)->toBe($admin->id);
});

test('admin can approve pending link', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $link = ParentStudentLink::factory()->pending()->create([
        'center_id' => $center->id,
    ]);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/parent-links/{$link->id}", [
        'action' => 'approve',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'Active');

    $link->refresh();
    expect($link->status)->toBe(ParentLinkStatus::Active);
});

test('admin can reject pending link', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $link = ParentStudentLink::factory()->pending()->create([
        'center_id' => $center->id,
    ]);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/parent-links/{$link->id}", [
        'action' => 'reject',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'Revoked');

    $link->refresh();
    expect($link->status)->toBe(ParentLinkStatus::Revoked);
});

test('admin can revoke active link', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $link = ParentStudentLink::factory()->create([
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/parent-links/{$link->id}", [
        'action' => 'revoke',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'Revoked');

    $link->refresh();
    expect($link->status)->toBe(ParentLinkStatus::Revoked);
});

test('admin can list pending requests for a center', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    ParentStudentLink::factory()->pending()->count(2)->create([
        'center_id' => $center->id,
    ]);

    // Active link should not appear
    ParentStudentLink::factory()->create([
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/parents/pending-requests");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

test('admin can list parent links for a student', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    ParentStudentLink::factory()->count(2)->create([
        'student_user_id' => $student->id,
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $response = $this->getJson("/api/v1/admin/students/{$student->id}/parent-links");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

/*
|--------------------------------------------------------------------------
| 6.12 — Unbranded center linking (auto-match across unbranded centers)
|--------------------------------------------------------------------------
*/

test('parent auto-link works for center-scoped students', function (): void {
    $center = $this->createParentPortalEnabledCenter();

    // Student in this center with parent_phone
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'parent_phone' => '7777777777',
    ]);

    // Parent registers with matching phone
    $parent = User::factory()->create([
        'phone' => '7777777777',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_parent' => true,
    ]);

    /** @var \App\Services\Parents\ParentService $service */
    $service = app(\App\Services\Parents\Contracts\ParentServiceInterface::class);
    $links = $service->autoLinkByPhone($parent, (int) $center->id);

    expect($links)->toHaveCount(1);
    expect($links->first()->student_user_id)->toBe($student->id);
});
