<?php

declare(strict_types=1);

namespace App\Services\Evolution;

use App\Models\EvolutionWebhookLog;
use App\Services\Logging\LogContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookService
{
    public function __construct(private readonly LogContextResolver $logContextResolver) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Request $request, array $payload): void
    {
        if (! $this->isAuthorized($request)) {
            $this->persistLog($request, $payload, 'rejected', 401, 'Evolution webhook secret is invalid.');

            Log::warning('Evolution webhook rejected due to invalid secret.', $this->resolveLogContext([
                'header' => $this->secretHeaderName(),
            ]));

            throw new \RuntimeException('Evolution webhook secret is invalid.');
        }

        $this->persistLog($request, $payload, 'accepted', 200);

        Log::channel('domain')->info('evolution_webhook_received', $this->resolveLogContext([
            'event' => $payload['event'] ?? null,
            'instance' => $payload['instance'] ?? null,
            'data' => $payload['data'] ?? null,
        ]));
    }

    private function isAuthorized(Request $request): bool
    {
        $secret = (string) config('evolution.webhook_secret', '');

        if ($secret === '') {
            return true;
        }

        $provided = $request->headers->get($this->secretHeaderName(), '');

        return is_string($provided) && hash_equals($secret, $provided);
    }

    private function secretHeaderName(): string
    {
        return (string) config('evolution.webhook_secret_header', 'x-evolution-secret');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistLog(
        Request $request,
        array $payload,
        string $status,
        int $responseCode,
        ?string $errorMessage = null
    ): void {
        EvolutionWebhookLog::query()->create([
            'request_id' => $this->resolveRequestId($request),
            'instance' => isset($payload['instance']) && is_string($payload['instance']) ? $payload['instance'] : null,
            'event' => isset($payload['event']) && is_string($payload['event']) ? $payload['event'] : null,
            'status' => $status,
            'response_code' => $responseCode,
            'error_message' => $errorMessage,
            'headers' => $this->resolveLoggableHeaders($request),
            'payload' => $payload,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resolveLoggableHeaders(Request $request): array
    {
        $headers = [];

        foreach (['user-agent', 'content-type', 'x-forwarded-for', 'x-real-ip'] as $header) {
            $value = $request->headers->get($header);

            if (is_string($value) && $value !== '') {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    private function resolveRequestId(Request $request): ?string
    {
        $requestId = $request->headers->get('X-Request-Id')
            ?? $request->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return $this->logContextResolver->resolve($overrides);
    }
}
