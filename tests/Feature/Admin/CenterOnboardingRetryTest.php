<?php

declare(strict_types=1);

use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class)->group('centers', 'onboarding', 'admin');

beforeEach(function (): void {
    $this->withoutMiddleware();
    $this->asAdmin();
});

it('retries onboarding without duplicating the owner user', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);
    Bus::fake();

    $center = Center::factory()->create([
        'slug' => 'retry-center',
        'type' => 0,
        'name_translations' => ['en' => 'Retry Center'],
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);

    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
        'email' => 'retry-owner@example.com',
    ]);

    $center->users()->syncWithoutDetaching([
        $owner->id => ['type' => 'owner'],
    ]);

    $before = User::where('center_id', $center->id)->count();

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/onboarding/retry", [], $this->adminHeaders());

    $response->assertOk();

    $after = User::where('center_id', $center->id)->count();

    expect($after)->toBe($before)
        ->and($center->fresh()?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);

    Bus::assertDispatched(SendAdminInvitationEmailJob::class);
});

it('bulk retries onboarding for multiple centers', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);
    Bus::fake();

    $centerA = Center::factory()->create([
        'slug' => 'retry-a',
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);
    $centerB = Center::factory()->create([
        'slug' => 'retry-b',
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);

    $ownerA = User::factory()->create([
        'center_id' => $centerA->id,
        'is_student' => false,
        'email' => 'retry-a@example.com',
    ]);
    $ownerB = User::factory()->create([
        'center_id' => $centerB->id,
        'is_student' => false,
        'email' => 'retry-b@example.com',
    ]);

    $centerA->users()->syncWithoutDetaching([$ownerA->id => ['type' => 'owner']]);
    $centerB->users()->syncWithoutDetaching([$ownerB->id => ['type' => 'owner']]);

    $response = $this->postJson('/api/v1/admin/centers/bulk-onboarding-retry', [
        'center_ids' => [$centerA->id, $centerB->id, 999999],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.retried', 2)
        ->assertJsonPath('data.counts.failed', 1);

    expect($centerA->fresh()?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE)
        ->and($centerB->fresh()?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);
});
