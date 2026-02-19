<?php

declare(strict_types=1);

use App\Jobs\SendAdminPasswordResetEmailJob;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('jobs', 'email');

it('skips invite sending when invitation was already sent', function (): void {
    $admin = User::factory()->create([
        'is_student' => false,
        'invitation_sent_at' => now(),
    ]);

    Notification::fake();
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('createToken')->never();

    $job = new SendAdminPasswordResetEmailJob((int) $admin->id, true);
    $job->handle();

    Notification::assertNothingSent();
});

it('sends invite reset email and records invitation timestamp', function (): void {
    $admin = User::factory()->create([
        'is_student' => false,
        'invitation_sent_at' => null,
    ]);

    Notification::fake();
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('createToken')->once()->andReturn('token');

    $job = new SendAdminPasswordResetEmailJob((int) $admin->id, true);
    $job->handle();

    $admin->refresh();

    expect($admin->invitation_sent_at)->not->toBeNull();
    Notification::assertSentTo($admin, AdminPasswordResetNotification::class);
});

it('sends non-invite reset email without setting invitation timestamp', function (): void {
    $admin = User::factory()->create([
        'is_student' => false,
        'invitation_sent_at' => null,
    ]);

    Notification::fake();
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('createToken')->once()->andReturn('token');

    $job = new SendAdminPasswordResetEmailJob((int) $admin->id, false);
    $job->handle();

    $admin->refresh();

    expect($admin->invitation_sent_at)->toBeNull();
    Notification::assertSentTo($admin, AdminPasswordResetNotification::class);
});
