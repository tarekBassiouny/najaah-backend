<?php

declare(strict_types=1);

use App\Jobs\CreateBunnyLibraryJob;
use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use App\Services\Centers\CenterOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class)->group('centers', 'onboarding', 'admin');

it('retries onboarding without duplicating the owner user', function (): void {
    Role::factory()->create(['slug' => 'center_owner']);
    Bus::fake();

    $service = app(CenterOnboardingService::class);

    $result = $service->onboard(
        [
            'slug' => 'retry-center',
            'type' => 0,
            'name_translations' => ['en' => 'Retry Center'],
        ],
        null,
        [
            'name' => 'Owner Name',
            'email' => 'retry-owner@example.com',
            'phone' => '1999000000',
        ],
        'center_owner'
    );

    $center = $result['center']->fresh();
    expect($center)->not->toBeNull();

    $center?->update(['onboarding_status' => Center::ONBOARDING_FAILED]);

    $before = User::where('center_id', $center?->id)->count();

    $service->resume($center, null, [
        'name' => 'Owner Name',
        'email' => 'retry-owner@example.com',
        'phone' => '1999000000',
    ], 'center_owner');

    $after = User::where('center_id', $center?->id)->count();

    expect($after)->toBe($before)
        ->and($center?->fresh()?->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);

    Bus::assertDispatched(CreateBunnyLibraryJob::class);
    Bus::assertDispatched(SendAdminInvitationEmailJob::class);
});
