<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Actions\Auth\APILoginAction;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_issues_tokens(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $device */
        $device = UserDevice::factory()->make(['user_id' => $user->id]);

        /** @var MockInterface&APILoginAction $action */
        $action = Mockery::mock(APILoginAction::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $action->shouldReceive('execute')
            ->once()
            ->with([
                'otp' => '123456',
                'token' => 'token-123',
                'device_uuid' => 'device-123',
            ])
            ->andReturn([
                'user' => $user,
                'device' => $device,
                'tokens' => ['access_token' => 'access', 'refresh_token' => 'refresh'],
            ]);

        $this->app->instance(APILoginAction::class, $action);

        $response = $this->postJson('/api/v1/auth/verify', [
            'otp' => '123456',
            'token' => 'token-123',
            'device_uuid' => 'device-123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'device', 'tokens' => ['access_token', 'refresh_token']]);
    }
}
