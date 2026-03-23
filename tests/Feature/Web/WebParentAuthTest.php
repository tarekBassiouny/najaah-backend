<?php

declare(strict_types=1);

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class)->group('web', 'auth', 'parent');

function parentCenterApiKey(Center $center): string
{
    if (! is_string($center->api_key) || $center->api_key === '') {
        $center->api_key = 'center-key-'.$center->id;
        $center->save();
    }

    return $center->api_key;
}

/*
|--------------------------------------------------------------------------
| 6.2 — Web parent auth: register, auto-link, login, refresh
|--------------------------------------------------------------------------
*/

test('parent register creates user and returns otp token', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()->send('1234567890', '+20', (int) $center->id)->andReturn('otp-token-parent');
    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/web/auth/parent/register', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('auto_linked', false)
        ->assertJsonStructure(['success', 'token', 'auto_linked']);

    $this->assertDatabaseHas('users', [
        'phone' => '1234567890',
        'is_parent' => true,
    ]);
});

test('parent register auto-links matching students by phone', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

    User::factory()->create([
        'phone' => '9999999999',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'parent_phone' => '1234567890',
    ]);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()->send('1234567890', '+20', (int) $center->id)->andReturn('otp-token-auto');
    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/web/auth/parent/register', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('auto_linked', true);

    $parent = User::where('phone', '1234567890')->where('is_parent', true)->first();
    expect(ParentStudentLink::where('parent_user_id', $parent->id)->count())->toBe(1);

    $link = ParentStudentLink::where('parent_user_id', $parent->id)->first();
    expect($link->status)->toBe(ParentLinkStatus::Active);
    expect($link->link_method)->toBe(ParentLinkMethod::AutoMatched);
});

test('parent send-otp returns token', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

    User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_parent' => true,
    ]);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()->send('1234567890', '+20', (int) $center->id)->andReturn('otp-token-login');
    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/web/auth/parent/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('token', 'otp-token-login');
});

test('parent verify returns user data and tokens', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

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
        'otp_token' => 'verify-parent-token',
        'user_id' => $parent->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/parent/verify', [
        'otp' => '123456',
        'token' => 'verify-parent-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.is_parent', true)
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'phone', 'is_parent'],
            'token' => ['access_token', 'refresh_token'],
            'device_uuid',
        ]);

    $this->assertDatabaseHas('jwt_tokens', [
        'user_id' => $parent->id,
        'platform' => TokenPlatform::Web->value,
    ]);
});

test('parent verify rejects non-parent user', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'is_parent' => false,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'reject-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/parent/verify', [
        'otp' => '123456',
        'token' => 'reject-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

test('parent refresh returns new tokens', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $parent = $this->asWebParent(center: $center);

    $refreshToken = JwtToken::where('user_id', $parent->id)->first()->refresh_token;

    $response = $this->postJson('/api/v1/web/auth/parent/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['token' => ['access_token', 'refresh_token']]);
});

test('parent me returns profile with linked students', function (): void {
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

    $response = $this->getJson('/api/v1/web/auth/parent/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.is_parent', true)
        ->assertJsonCount(1, 'data.linked_students');
});

test('parent logout revokes token', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $this->asWebParent(center: $center);

    $response = $this->postJson('/api/v1/web/auth/parent/logout');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('parent web token has platform web', function (): void {
    $center = $this->createParentPortalEnabledCenter();
    $apiKey = parentCenterApiKey($center);

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
        'otp_token' => 'platform-parent-token',
        'user_id' => $parent->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson('/api/v1/web/auth/parent/verify', [
        'otp' => '123456',
        'token' => 'platform-parent-token',
    ], ['X-Api-Key' => $apiKey])->assertOk();

    $jwtRecord = JwtToken::where('user_id', $parent->id)->latest()->first();
    expect($jwtRecord->platform)->toBe(TokenPlatform::Web);
});
