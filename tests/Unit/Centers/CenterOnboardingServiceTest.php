<?php

declare(strict_types=1);

use App\Jobs\CreateCenterBunnyLibrary;
use App\Jobs\SendCenterOnboardingEmail;
use App\Models\Role;
use App\Services\Centers\CenterOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('dispatches onboarding jobs after center creation', function (): void {
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

    Bus::assertDispatched(SendCenterOnboardingEmail::class);
    Bus::assertDispatched(CreateCenterBunnyLibrary::class);

    expect($result['center']->id)->not->toBeNull()
        ->and($result['owner']->id)->not->toBeNull()
        ->and($result['email_sent'])->toBeTrue();
});
