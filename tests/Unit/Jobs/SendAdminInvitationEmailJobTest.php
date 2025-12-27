<?php

declare(strict_types=1);

use App\Jobs\SendAdminInvitationEmailJob;
use App\Models\Center;
use App\Models\User;
use App\Notifications\AdminCenterOnboardingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('jobs', 'email');

it('skips sending invitation when already sent', function (): void {
    $center = Center::factory()->create();
    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
        'invitation_sent_at' => now(),
    ]);

    Notification::fake();
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('createToken')->never();

    $job = new SendAdminInvitationEmailJob($center->id, $owner->id);
    $job->handle();

    Notification::assertNothingSent();
});

it('sends invitation email once and records timestamp', function (): void {
    $center = Center::factory()->create();
    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
        'invitation_sent_at' => null,
    ]);

    Notification::fake();
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('createToken')->once()->andReturn('token');

    $job = new SendAdminInvitationEmailJob($center->id, $owner->id);
    $job->handle();

    $owner->refresh();
    expect($owner->invitation_sent_at)->not->toBeNull();

    Notification::assertSentTo($owner, AdminCenterOnboardingNotification::class);
});

it('marks center failed when job fails', function (): void {
    $center = Center::factory()->create(['onboarding_status' => Center::ONBOARDING_IN_PROGRESS]);
    $owner = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => false,
    ]);

    $job = new SendAdminInvitationEmailJob($center->id, $owner->id);
    $job->failed(new RuntimeException('fail'));

    $center->refresh();
    expect($center->onboarding_status)->toBe(Center::ONBOARDING_FAILED);
});
