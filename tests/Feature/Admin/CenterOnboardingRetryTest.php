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
