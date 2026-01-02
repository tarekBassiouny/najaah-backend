<?php

declare(strict_types=1);

use App\Actions\Admin\Centers\CreateCenterAction;
use App\Actions\Admin\Centers\RetryCenterOnboardingAction;
use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('center', 'actions', 'onboarding', 'admin');

it('marks onboarding as active and dispatches invitation email', function (): void {
    Bus::fake();
    Role::factory()->create([
        'slug' => 'center_owner',
    ]);

    $action = app(CreateCenterAction::class);
    $result = $action->execute([
        'slug' => 'center-a',
        'type' => 0,
        'name_translations' => ['en' => 'Center A'],
        'admin' => [
            'name' => 'Owner Name',
            'email' => 'owner@example.com',
            'phone' => '1999000000',
        ],
    ]);

    expect($result['center']->id)->not->toBeNull()
        ->and($result['owner']->id)->not->toBeNull()
        ->and($result['email_sent'])->toBeTrue()
        ->and($result['center']->onboarding_status)->toBe(Center::ONBOARDING_ACTIVE);

    Bus::assertDispatched(SendAdminInvitationEmailJob::class);
});

it('marks onboarding as failed when a step throws', function (): void {
    Bus::fake();
    $action = app(CreateCenterAction::class);

    $exception = null;
    try {
        $action->execute([
            'slug' => 'center-b',
            'type' => 0,
            'name_translations' => ['en' => 'Center B'],
            'admin' => [
                'name' => 'Owner Name',
                'email' => 'owner-b@example.com',
                'phone' => '1999000000',
            ],
        ]);
    } catch (ValidationException $validationException) {
        $exception = $validationException;
    }

    expect($exception)->not->toBeNull();

    $center = Center::where('slug', 'center-b')->firstOrFail();
    expect($center->onboarding_status)->toBe(Center::ONBOARDING_FAILED);
});

it('is idempotent when retrying onboarding', function (): void {
    Bus::fake();
    Role::factory()->create([
        'slug' => 'center_owner',
    ]);

    $center = Center::factory()->create([
        'slug' => 'center-c',
        'type' => 0,
        'name_translations' => ['en' => 'Center C'],
        'onboarding_status' => Center::ONBOARDING_FAILED,
    ]);

    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
    ]);

    $center->users()->syncWithoutDetaching([
        $owner->id => ['type' => 'owner'],
    ]);

    $ownerCount = User::where('center_id', $center->id)->count();

    $action = app(RetryCenterOnboardingAction::class);
    $action->execute($center);

    $afterCount = User::where('center_id', $center->id)->count();

    expect($afterCount)->toBe($ownerCount);
});
