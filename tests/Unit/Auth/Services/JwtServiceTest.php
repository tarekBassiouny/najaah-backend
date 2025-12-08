<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\JwtService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, DatabaseTransactions::class)->group('auth', 'services');

test('create stores refresh token and returns tokens', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var UserDevice $device */
    $device = UserDevice::factory()->create(['user_id' => $user->id]);

    $mockJwt = new class
    {
        public function fromUser(User $user): string
        {
            return 'access-token';
        }
    };

    \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::swap($mockJwt);

    $service = new JwtService;
    $result = $service->create($user, $device);

    expect($result['access_token'])->toBe('access-token');
    expect($result['refresh_token'])->toBeString();
    assertDatabaseHas('jwt_tokens', [
        'user_id' => $user->id,
        'device_id' => $device->id,
    ]);
});
