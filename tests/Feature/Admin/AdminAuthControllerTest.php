<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class)->group('admin', 'auth');

/**
 * LOGIN
 */
test('admin login succeeds with valid credentials', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'secret123',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'email'],
                'token',
            ],
        ])
        ->assertJson(['success' => true]);
});

test('admin login fails with invalid credentials', function () {
    $response = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'invalid',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
            ],
        ]);
});

test('student cannot login as admin', function () {
    $student = User::factory()->create([
        'email' => 'student@test.com',
        'password' => 'pass',
        'is_student' => true,
    ]);

    $response = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'student@test.com',
        'password' => 'pass',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    // Even if credentials match, admin service blocks student login
    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
            ],
        ]);
});

test('center-scoped admin login fails when api key center does not match', function () {
    $centerA = Center::factory()->create([
        'api_key' => 'center-a-login-key',
    ]);
    Center::factory()->create([
        'api_key' => 'center-b-login-key',
    ]);

    User::factory()->create([
        'email' => 'center.admin@example.com',
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);

    $response = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'center.admin@example.com',
        'password' => 'secret123',
    ], [
        'X-Api-Key' => 'center-b-login-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('center-scoped admin login succeeds when api key center matches', function () {
    $center = Center::factory()->create([
        'api_key' => 'center-login-key',
    ]);

    User::factory()->create([
        'email' => 'matching.center.admin@example.com',
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $response = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'matching.center.admin@example.com',
        'password' => 'secret123',
    ], [
        'X-Api-Key' => 'center-login-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.center_id', $center->id);
});

/**
 * ME
 */
test('admin can fetch their profile', function () {
    $admin = $this->asAdmin(); // generates token + stores header

    $response = $this->getJson('/api/v1/admin/auth/me', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'status',
                    'status_key',
                    'status_label',
                    'center_id',
                    'roles',
                    'roles_with_permissions',
                    'scope_type',
                    'scope_center_id',
                    'is_system_super_admin',
                    'is_center_super_admin',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => ['id' => $admin->id],
            ],
        ]);
});

test('unauthenticated request to me fails', function () {
    $response = $this->getJson('/api/v1/admin/auth/me');

    $response->assertStatus(401);
});

test('forgot password sends reset link for admin account', function () {
    Notification::fake();
    $admin = User::factory()->create([
        'email' => 'forgot.admin@example.com',
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson('/api/v1/admin/auth/password/forgot', [
        'email' => 'forgot.admin@example.com',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($admin, AdminPasswordResetNotification::class);
});

test('forgot password returns success for unknown email without disclosure', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/admin/auth/password/forgot', [
        'email' => 'missing.admin@example.com',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertNothingSent();
});

/**
 * REFRESH
 */
test('admin can refresh token', function () {
    $admin = $this->asAdmin();

    $oldToken = $this->adminToken;

    $response = $this->postJson('/api/v1/admin/auth/refresh', [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonStructure(['success', 'data' => ['token']])
        ->assertJson(['success' => true]);

    $newToken = $response->json('data.token');

    expect($newToken)->not->toBe($oldToken);
});

test('refresh fails without token', function () {
    $response = $this->postJson('/api/v1/admin/auth/refresh');

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_MISSING',
            ],
        ]);
});

/**
 * LOGOUT
 */
test('admin can logout successfully', function () {
    $admin = $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/auth/logout', [], $this->adminHeaders());

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'message' => 'Logged out',
            ],
        ]);
});

test('logout fails without token', function () {
    $response = $this->postJson('/api/v1/admin/auth/logout');

    $response->assertStatus(401);
});

test('admin can change password with current password', function () {
    $admin = $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/auth/change-password', [
        'current_password' => 'secret123',
        'new_password' => 'newsecret123',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);

    $oldLogin = $this->postJson('/api/v1/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'secret123',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);
    $oldLogin->assertStatus(401);

    $newLogin = $this->postJson('/api/v1/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'newsecret123',
    ], [
        'X-Api-Key' => config('services.system_api_key'),
    ]);
    $newLogin->assertOk()
        ->assertJsonPath('success', true);
});

test('change password fails when current password is invalid', function () {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/auth/change-password', [
        'current_password' => 'wrong-password',
        'new_password' => 'newsecret123',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
});
