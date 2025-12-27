<?php

declare(strict_types=1);

use App\Jobs\CreateBunnyLibraryJob;
use App\Jobs\ProcessCenterLogoJob;
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

it('dispatches onboarding jobs after center creation', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'center-dispatch',
        'type' => 1,
        'name_translations' => ['en' => 'Center Dispatch'],
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#123456',
        'owner' => [
            'name' => 'Owner User',
            'email' => 'owner-dispatch@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated();

    $centerId = $response->json('data.center.id');
    $ownerId = $response->json('data.owner.id');

    Bus::assertDispatched(CreateBunnyLibraryJob::class, fn ($job) => $job->centerId === $centerId);
    Bus::assertDispatched(SendAdminInvitationEmailJob::class, fn ($job) => $job->centerId === $centerId && $job->ownerId === $ownerId);
    Bus::assertDispatched(ProcessCenterLogoJob::class, fn ($job) => $job->centerId === $centerId);
});

it('re-running onboarding does not dispatch completed jobs', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $center = Center::factory()->create([
        'logo_url' => 'https://example.com/logo.png',
        'branding_metadata' => [
            'logo_source' => 'https://example.com/logo.png',
            'logo_processed_at' => now()->toISOString(),
        ],
        'bunny_library_id' => 99,
        'onboarding_status' => Center::ONBOARDING_ACTIVE,
    ]);

    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
        'invitation_sent_at' => now(),
    ]);

    $center->users()->syncWithoutDetaching([
        $owner->id => ['type' => 'owner'],
    ]);

    $service = app(\App\Services\Centers\CenterOnboardingService::class);
    $service->resume($center, $owner, null, 'center_owner');

    Bus::assertNotDispatched(CreateBunnyLibraryJob::class);
    Bus::assertNotDispatched(SendAdminInvitationEmailJob::class);
    Bus::assertNotDispatched(ProcessCenterLogoJob::class);
});
