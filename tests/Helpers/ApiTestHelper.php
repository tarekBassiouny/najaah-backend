<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait ApiTestHelper
{
    private ?string $apiBearerToken = null;

    private ?User $apiUser = null;

    public function asApiUser(?User $user = null, ?string $token = null, ?string $deviceUuid = null): User
    {
        if ($user === null) {
            /** @var User $user */
            $user = User::factory()->create([
                'password' => 'secret123',
                'is_student' => true,  // API users are students
            ]);
        }

        // If token is passed manually → use it as-is
        if ($token !== null) {
            $this->apiBearerToken = $token;
        } else {
            Auth::guard('api')->logout();
            // Otherwise generate a real JWT token using the mobile guard
            $this->apiBearerToken = (string) Auth::guard('api')->attempt([
                'email' => $user->email,
                'password' => 'secret123',
                'is_student' => $user->is_student,
            ]);
        }

        if ($this->apiBearerToken === '') {
            $this->apiBearerToken = JWTAuth::fromUser($user);
        }

        if ($this->apiBearerToken !== null) {
            $deviceId = $deviceUuid ?? 'device-123';
            $device = UserDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $deviceId,
                ],
                [
                    'model' => 'device-model',
                    'os_version' => 'os-version',
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => now(),
                    'last_used_at' => now(),
                ]
            );

            JwtToken::create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'access_token' => $this->apiBearerToken,
                'refresh_token' => Str::random(40),
                'expires_at' => now()->addMinutes(30),
                'refresh_expires_at' => now()->addDays(30),
            ]);
        }

        $this->apiUser = $user;

        return $user;
    }

    public function apiGet(string $uri, array $headers = []): TestResponse
    {
        return $this->getJson($uri, $this->withApiHeaders($headers));
    }

    public function apiPost(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->postJson($uri, $data, $this->withApiHeaders($headers));
    }

    public function apiPut(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->putJson($uri, $data, $this->withApiHeaders($headers));
    }

    public function apiPatch(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->patchJson($uri, $data, $this->withApiHeaders($headers));
    }

    public function apiDelete(string $uri, array $headers = []): TestResponse
    {
        return $this->deleteJson($uri, [], $this->withApiHeaders($headers));
    }

    private function withApiHeaders(array $headers): array
    {
        // Ensure we always have a token when calling mobile API endpoints
        if ($this->apiBearerToken === null) {
            $this->asApiUser();
        }

        $baseHeaders = ['Accept' => 'application/json'];

        if ($this->apiBearerToken !== null) {
            $baseHeaders['Authorization'] = 'Bearer '.$this->apiBearerToken;
        }

        if (! array_key_exists('X-Api-Key', $headers)) {
            $baseHeaders['X-Api-Key'] = $this->resolveApiKey();
        }

        return array_merge($baseHeaders, $headers);
    }

    private function resolveApiKey(): string
    {
        $systemKey = (string) Config::get('services.system_api_key', '');
        if ($systemKey === '') {
            $systemKey = 'system-test-key';
            Config::set('services.system_api_key', $systemKey);
        }

        if ($this->apiUser instanceof User && is_numeric($this->apiUser->center_id)) {
            $center = Center::find((int) $this->apiUser->center_id);
            if ($center instanceof Center) {
                if (! is_string($center->api_key) || $center->api_key === '') {
                    $center->api_key = 'center-key-'.$center->id;
                    $center->save();
                }

                return $center->api_key;
            }
        }

        return $systemKey;
    }
}
