<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
                'user' => ['id', 'email'],
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
