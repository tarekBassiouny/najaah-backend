<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'api-key-scope');

beforeEach(function (): void {
    config(['services.system_api_key' => 'system-key']);
});

it('rejects branded student requests made with system api key', function (): void {
    $center = Center::factory()->create([
        'type' => 1,
        'api_key' => 'center-a-key',
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/auth/me', [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('rejects system student requests made with center api key', function (): void {
    $center = Center::factory()->create([
        'type' => 1,
        'api_key' => 'center-a-key',
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/auth/me', [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('rejects branded student requests made with a different center api key', function (): void {
    $centerA = Center::factory()->create([
        'type' => 1,
        'api_key' => 'center-a-key',
    ]);
    $centerB = Center::factory()->create([
        'type' => 1,
        'api_key' => 'center-b-key',
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore', [
        'X-Api-Key' => $centerB->api_key,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('rejects branded student requests when center api key belongs to an inactive center', function (): void {
    $center = Center::factory()->create([
        'type' => 1,
        'api_key' => 'center-a-key',
        'status' => Center::STATUS_INACTIVE,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/auth/me', [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('allows system student requests with system api key', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/courses/explore', [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);
});
