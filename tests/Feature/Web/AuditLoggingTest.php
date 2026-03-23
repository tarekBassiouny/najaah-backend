<?php

declare(strict_types=1);

use App\Enums\ParentLinkStatus;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\OtpCode;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Support\AuditActions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'audit');

function auditCenterApiKey(Center $center): string
{
    if (! is_string($center->api_key) || $center->api_key === '') {
        $center->api_key = 'center-key-'.$center->id;
        $center->save();
    }

    return $center->api_key;
}

/*
|--------------------------------------------------------------------------
| 6.14 — Audit logging: link creation, approval, revocation logged
|--------------------------------------------------------------------------
*/

test('student web login is audit logged', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = auditCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'audit-student-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'audit-student-token',
    ], ['X-Api-Key' => $apiKey])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $student->id,
        'action' => AuditActions::STUDENT_LOGIN,
    ]);
});

test('parent web login is audit logged', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = auditCenterApiKey($center);

    $parent = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_parent' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'audit-parent-token',
        'user_id' => $parent->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson('/api/v1/web/auth/parent/verify', [
        'otp' => '123456',
        'token' => 'audit-parent-token',
    ], ['X-Api-Key' => $apiKey])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $parent->id,
        'action' => AuditActions::STUDENT_LOGIN,
    ]);
});

test('admin link creation is audit logged', function (): void {
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

    $this->postJson("/api/v1/admin/centers/{$center->id}/parent-links", [
        'parent_user_id' => $parent->id,
        'student_user_id' => $student->id,
    ])->assertStatus(201);

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditActions::PARENT_LINK_CREATED,
    ]);
});

test('admin link approval is audit logged', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $link = ParentStudentLink::factory()->pending()->create([
        'center_id' => $center->id,
    ]);

    $this->patchJson("/api/v1/admin/centers/{$center->id}/parent-links/{$link->id}", [
        'action' => 'approve',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditActions::PARENT_LINK_APPROVED,
    ]);
});

test('admin link revocation is audit logged', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $link = ParentStudentLink::factory()->create([
        'center_id' => $center->id,
        'status' => ParentLinkStatus::Active,
    ]);

    $this->patchJson("/api/v1/admin/centers/{$center->id}/parent-links/{$link->id}", [
        'action' => 'revoke',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditActions::PARENT_LINK_REVOKED,
    ]);
});
