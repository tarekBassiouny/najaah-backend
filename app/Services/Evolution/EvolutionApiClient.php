<?php

declare(strict_types=1);

namespace App\Services\Evolution;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class EvolutionApiClient
{
    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->request()
            ->get('/')
            ->throw()
            ->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchInstances(): array
    {
        return $this->request()
            ->get('/instance/fetchInstances')
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createInstance(array $payload): array
    {
        return $this->request()
            ->post('/instance/create', $payload)
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendText(string $instanceName, array $payload): array
    {
        $request = $this->request();
        $instanceToken = (string) config('evolution.otp_instance_token', '');

        if ($instanceToken !== '') {
            $request = $request->replaceHeaders([
                'apikey' => $instanceToken,
            ]);
        }

        return $request
            ->post('/message/sendText/' . $instanceName, $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function findWebhook(string $instanceName): array
    {
        return $this->request()
            ->get('/webhook/find/' . $instanceName)
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function setWebhook(string $instanceName, array $payload): array
    {
        return $this->request()
            ->post('/webhook/set/' . $instanceName, [
                'webhook' => $payload,
            ])
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        $baseUrl = (string) config('evolution.base_url', '');
        $apiKey = (string) config('evolution.api_key', '');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Evolution API credentials are missing.');
        }

        return Http::acceptJson()
            ->baseUrl($baseUrl)
            ->timeout((int) config('evolution.timeout', 15))
            ->withHeaders([
                'apikey' => $apiKey,
            ]);
    }
}
