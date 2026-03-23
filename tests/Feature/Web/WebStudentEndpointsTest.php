<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'student', 'endpoints');

/*
|--------------------------------------------------------------------------
| 6.5 — Student web endpoints: verify reused mobile controllers work
|--------------------------------------------------------------------------
*/

test('web student can access explore endpoint', function (): void {
    $center = $this->createWebEnabledCenter();
    $this->asWebStudent(center: $center);

    $response = $this->getJson('/api/v1/web/courses/explore');

    expect($response->status())->not->toBe(403);
    expect($response->status())->not->toBe(401);
});

test('web student can access enrolled courses', function (): void {
    $center = $this->createWebEnabledCenter();
    $this->asWebStudent(center: $center);

    $response = $this->getJson('/api/v1/web/courses/enrolled');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('web student can access me endpoint', function (): void {
    $center = $this->createWebEnabledCenter();
    $student = $this->asWebStudent(center: $center);

    $response = $this->getJson('/api/v1/web/auth/student/me');

    $response->assertOk()
        ->assertJsonPath('data.id', $student->id);
});

test('web student cannot access protected route without auth', function (): void {
    config(['services.system_api_key' => 'system-key']);

    // /auth/student/me requires jwt.web.student — should reject unauthenticated
    $response = $this->getJson('/api/v1/web/auth/student/me', [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertStatus(403);
});

test('web student cannot access with mobile token platform', function (): void {
    $center = $this->createWebEnabledCenter();
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
        'status' => 1,
    ]);

    // Use mobile API helper — creates a Mobile platform token
    $this->asApiUser($student);

    // The web endpoint should reject — token is Mobile platform, not Web
    $response = $this->getJson('/api/v1/web/auth/student/me');

    $response->assertStatus(403);
});
