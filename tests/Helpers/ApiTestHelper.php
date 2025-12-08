<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

trait ApiTestHelper
{
    private ?string $apiBearerToken = null;

    private ?User $apiUser = null;

    public function asApiUser(?User $user = null, ?string $token = null): User
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
            // Otherwise generate a real JWT token using the mobile guard
            $this->apiBearerToken = (string) Auth::guard('api')->attempt([
                'email' => $user->email,
                'password' => 'secret123',
                'is_student' => true,
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

        return array_merge($baseHeaders, $headers);
    }
}
