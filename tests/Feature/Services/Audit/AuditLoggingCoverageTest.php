<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Center;
use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('audit', 'services', 'admin');

it('audits category lifecycle actions', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();

    $createResponse = $this->postJson("/api/v1/admin/centers/{$center->id}/categories", [
        'title_translations' => ['en' => 'Audit Category'],
        'description_translations' => ['en' => 'Initial'],
    ], $this->adminHeaders());

    $createResponse->assertCreated();
    $categoryId = (int) $createResponse->json('data.id');
    $category = Category::findOrFail($categoryId);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::CATEGORY_CREATED,
        'entity_type' => Category::class,
        'entity_id' => $category->id,
    ]);

    $updateResponse = $this->putJson("/api/v1/admin/centers/{$center->id}/categories/{$category->id}", [
        'title_translations' => ['en' => 'Audit Category Updated'],
    ], $this->adminHeaders());

    $updateResponse->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::CATEGORY_UPDATED,
        'entity_type' => Category::class,
        'entity_id' => $category->id,
    ]);

    $deleteResponse = $this->deleteJson("/api/v1/admin/centers/{$center->id}/categories/{$category->id}", [], $this->adminHeaders());
    $deleteResponse->assertNoContent();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::CATEGORY_DELETED,
        'entity_type' => Category::class,
        'entity_id' => $category->id,
    ]);
});

it('audits student lifecycle actions', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();

    $createResponse = $this->postJson('/api/v1/admin/students', [
        'name' => 'Audit Student',
        'email' => 'audit.student@example.com',
        'phone' => '1225291843',
        'country_code' => '+20',
        'center_id' => $center->id,
    ], $this->adminHeaders());

    $createResponse->assertCreated();
    $studentId = (int) $createResponse->json('data.id');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::STUDENT_CREATED,
        'entity_type' => User::class,
        'entity_id' => $studentId,
    ]);

    $updateResponse = $this->putJson("/api/v1/admin/students/{$studentId}", [
        'name' => 'Audit Student Updated',
        'status' => 0,
    ], $this->adminHeaders());

    $updateResponse->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::STUDENT_UPDATED,
        'entity_type' => User::class,
        'entity_id' => $studentId,
    ]);

    $deleteResponse = $this->deleteJson("/api/v1/admin/students/{$studentId}", [], $this->adminHeaders());
    $deleteResponse->assertNoContent();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::STUDENT_DELETED,
        'entity_type' => User::class,
        'entity_id' => $studentId,
    ]);
});

it('audits instructor lifecycle actions', function (): void {
    $admin = $this->asAdmin();
    $center = \App\Models\Center::factory()->create();

    $createResponse = $this->postJson("/api/v1/admin/centers/{$center->id}/instructors", [
        'name_translations' => ['en' => 'Audit Instructor'],
        'bio_translations' => ['en' => 'Initial bio'],
    ], $this->adminHeaders());

    $createResponse->assertCreated();
    $instructorId = (int) $createResponse->json('data.id');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::INSTRUCTOR_CREATED,
        'entity_type' => Instructor::class,
        'entity_id' => $instructorId,
    ]);

    $updateResponse = $this->putJson("/api/v1/admin/centers/{$center->id}/instructors/{$instructorId}", [
        'bio_translations' => ['en' => 'Updated bio'],
    ], $this->adminHeaders());

    $updateResponse->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::INSTRUCTOR_UPDATED,
        'entity_type' => Instructor::class,
        'entity_id' => $instructorId,
    ]);

    $deleteResponse = $this->deleteJson("/api/v1/admin/centers/{$center->id}/instructors/{$instructorId}", [], $this->adminHeaders());
    $deleteResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Instructor deleted successfully');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::INSTRUCTOR_DELETED,
        'entity_type' => Instructor::class,
        'entity_id' => $instructorId,
    ]);
});

it('audits center operational actions for retry onboarding and logo upload', function (): void {
    Storage::fake('spaces');
    config()->set('filesystems.default', 'spaces');
    Bus::fake();

    $admin = $this->asAdmin();
    Role::factory()->create(['slug' => 'center_owner']);

    $center = Center::factory()->create([
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);

    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
        'email' => 'audit-center-owner@example.com',
    ]);
    $center->users()->syncWithoutDetaching([$owner->id => ['type' => 'owner']]);

    $retryResponse = $this->postJson("/api/v1/admin/centers/{$center->id}/onboarding/retry", [], $this->adminHeaders());
    $retryResponse->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::CENTER_ONBOARDING_RETRIED,
        'entity_type' => Center::class,
        'entity_id' => $center->id,
    ]);

    $logoFile = UploadedFile::fake()->image('audit-logo.png');
    $uploadResponse = $this->post("/api/v1/admin/centers/{$center->id}/branding/logo", [
        'logo' => $logoFile,
    ], $this->adminHeaders());
    $uploadResponse->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => AuditActions::CENTER_LOGO_UPDATED,
        'entity_type' => Center::class,
        'entity_id' => $center->id,
    ]);

    $this->assertGreaterThanOrEqual(
        2,
        AuditLog::query()
            ->where('user_id', $admin->id)
            ->where('entity_type', Center::class)
            ->where('entity_id', $center->id)
            ->whereIn('action', [AuditActions::CENTER_ONBOARDING_RETRIED, AuditActions::CENTER_LOGO_UPDATED])
            ->count()
    );
});
