<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'feature-flags');

function flagsCenterApiKey(Center $center): string
{
    if (! is_string($center->api_key) || $center->api_key === '') {
        $center->api_key = 'center-key-'.$center->id;
        $center->save();
    }

    return $center->api_key;
}

/*
|--------------------------------------------------------------------------
| 6.8 — Center feature flags: allow_web_access off / allow_parent_portal off
|--------------------------------------------------------------------------
*/

test('web student login blocked when allow_web_access is off', function (): void {
    /** @var Center $center */
    $center = Center::factory()->create();
    $apiKey = flagsCenterApiKey($center);

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => ['allow_web_access' => false, 'allow_parent_portal' => false],
    ]);

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
        'otp_token' => 'disabled-web-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'disabled-web-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'WEB_ACCESS_DISABLED');
});

test('web student middleware blocks when allow_web_access is off', function (): void {
    /** @var Center $center */
    $center = Center::factory()->create();

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => ['allow_web_access' => false],
    ]);

    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    $this->asWebStudent($student, $center);

    $response = $this->getJson('/api/v1/web/auth/student/me');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'WEB_ACCESS_DISABLED');
});

test('parent login blocked when allow_parent_portal is off', function (): void {
    /** @var Center $center */
    $center = Center::factory()->create();
    $apiKey = flagsCenterApiKey($center);

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => ['allow_web_access' => true, 'allow_parent_portal' => false],
    ]);

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
        'otp_token' => 'disabled-portal-token',
        'user_id' => $parent->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/parent/verify', [
        'otp' => '123456',
        'token' => 'disabled-portal-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'PARENT_PORTAL_DISABLED');
});

test('parent middleware blocks when allow_parent_portal is off', function (): void {
    /** @var Center $center */
    $center = Center::factory()->create();

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => ['allow_web_access' => true, 'allow_parent_portal' => false],
    ]);

    $parent = User::factory()->create([
        'center_id' => $center->id,
        'is_parent' => true,
        'status' => UserStatus::Active,
    ]);

    $this->asWebParent($parent, $center);

    $response = $this->getJson('/api/v1/web/auth/parent/me');

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'PARENT_PORTAL_DISABLED');
});

test('web student endpoint works when allow_web_access is on', function (): void {
    $center = $this->createWebEnabledCenter();
    $student = $this->asWebStudent(center: $center);

    $response = $this->getJson('/api/v1/web/auth/student/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id);
});

test('parent endpoint works when allow_parent_portal is on', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $response = $this->getJson('/api/v1/web/auth/parent/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $parent->id);
});
