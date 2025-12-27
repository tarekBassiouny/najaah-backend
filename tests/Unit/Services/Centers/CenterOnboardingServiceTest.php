<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use App\Services\Centers\CenterOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('center', 'services', 'onboarding', 'admin');

it('marks onboarding as active and applies storage defaults', function (): void {
    Bus::fake();
    Role::factory()->create([
        'slug' => 'center_owner',
    ]);

    $service = app(CenterOnboardingService::class);
    $result = $service->onboard(
        [
            'slug' => 'center-a',
            'type' => 0,
            'name_translations' => ['en' => 'Center A'],
        ],
        null,
        [
            'name' => 'Owner Name',
            'email' => 'owner@example.com',
            'phone' => '1999000000',
        ],
        'center_owner'
    );

    expect($result['center']->id)->not->toBeNull()
        ->and($result['owner']->id)->not->toBeNull()
        ->and($result['email_sent'])->toBeFalse()
        ->and($result['center']->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE)
        ->and($result['center']->storage_driver)->toBe('spaces')
        ->and($result['center']->storage_root)->toBe('centers/'.$result['center']->id);
});

it('marks onboarding as failed when a step throws', function (): void {
    Bus::fake();
    Role::factory()->create([
        'slug' => 'center_owner',
    ]);

    $service = app(CenterOnboardingService::class);

    $center = Center::factory()->create([
        'slug' => 'center-b',
        'type' => 0,
        'name_translations' => ['en' => 'Center B'],
        'onboarding_status' => Center::ONBOARDING_DRAFT,
    ]);

    $exception = null;
    try {
        $service->resume(
            $center,
            null,
            [
                'name' => 'Owner Name',
                'email' => 'owner-b@example.com',
                'phone' => '1999000000',
            ],
            'missing_role'
        );
    } catch (ValidationException $validationException) {
        $exception = $validationException;
    }

    expect($exception)->not->toBeNull();

    $center->refresh();
    expect($center->onboarding_status)->toBe(Center::ONBOARDING_FAILED);
});

it('is idempotent when resuming onboarding', function (): void {
    Bus::fake();
    Role::factory()->create([
        'slug' => 'center_owner',
    ]);

    $service = app(CenterOnboardingService::class);
    $result = $service->onboard(
        [
            'slug' => 'center-c',
            'type' => 0,
            'name_translations' => ['en' => 'Center C'],
        ],
        null,
        [
            'name' => 'Owner Name',
            'email' => 'owner-c@example.com',
            'phone' => '1999000000',
        ],
        'center_owner'
    );

    $center = $result['center']->fresh();
    expect($center)->not->toBeNull();

    $center?->update(['onboarding_status' => Center::ONBOARDING_FAILED]);

    $ownerCount = User::where('center_id', $center?->id)->count();

    $service->resume($center, null, [
        'name' => 'Owner Name',
        'email' => 'owner-c@example.com',
        'phone' => '1999000000',
    ], 'center_owner');

    $afterCount = User::where('center_id', $center?->id)->count();

    expect($afterCount)->toBe($ownerCount);
});
