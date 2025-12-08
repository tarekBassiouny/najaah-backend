<?php

declare(strict_types=1);

use App\Actions\Auth\APILoginAction;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class)->group('auth');

test('verify issues tokens', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var UserDevice $device */
    $device = UserDevice::factory()->make(['user_id' => $user->id]);

    /** @var MockInterface&APILoginAction $action */
    $action = Mockery::mock(APILoginAction::class);
    $action->allows()
        ->execute(Mockery::type('array'))
        ->andReturn([
            'user' => $user,
            'device' => $device,
            'tokens' => ['access_token' => 'access', 'refresh_token' => 'refresh'],
        ]);

    $this->app->instance(APILoginAction::class, $action);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['user', 'device', 'tokens']);
});
