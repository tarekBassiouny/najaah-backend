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

it('dispatches onboarding jobs after center creation', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $payload = [
        'slug' => 'center-dispatch',
        'type' => 'branded',
        'name' => 'Center Dispatch',
        'branding_metadata' => [
            'primary_color' => '#123456',
        ],
        'admin' => [
            'name' => 'Admin User',
            'email' => 'owner-dispatch@example.com',
        ],
    ];

    $response = $this->postJson('/api/v1/admin/centers', $payload);

    $response->assertCreated();

    $centerId = $response->json('data.center.id');
    $ownerId = $response->json('data.owner.id');

    $center = Center::find($centerId);
    expect($center?->getAttribute('bunny_library_id'))->toBeNull();

    Bus::assertDispatched(SendAdminInvitationEmailJob::class, fn ($job) => $job->centerId === $centerId && $job->ownerId === $ownerId);
});

it('re-running onboarding does not dispatch invitation when already sent', function (): void {
    Bus::fake();
    Role::factory()->create(['slug' => 'center_owner']);

    $center = Center::factory()->create([
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

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/onboarding/retry");

    $response->assertOk();

    Bus::assertNotDispatched(SendAdminInvitationEmailJob::class);
});
