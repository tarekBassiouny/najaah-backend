<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\User;
use Illuminate\Http\Request;

class LogContextResolver
{
    /**
     * Resolve logging context for use with Log::info/warning/error.
     *
     * Example:
     * Log::info('Job completed', $resolver->resolve(['source' => 'job']));
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function resolve(array $overrides = []): array
    {
        $context = [
            'center_id' => null,
            'user_id' => null,
            'request_id' => null,
            'source' => app()->runningInConsole() ? 'job' : 'api',
        ];

        $request = app()->bound('request') ? app('request') : null;
        if ($request instanceof Request) {
            $context['source'] = $this->resolveSourceFromRequest($request);

            $user = $request->user();
            if ($user instanceof User) {
                $context['user_id'] = $user->id;
                $context['center_id'] = $user->center_id;
            }

            $context['request_id'] = $this->resolveRequestId($request);
        }

        foreach ($overrides as $key => $value) {
            $context[$key] = $value;
        }

        return $context;
    }

    private function resolveSourceFromRequest(Request $request): string
    {
        $path = ltrim($request->path(), '/');

        if (str_contains($path, 'webhook')) {
            return 'webhook';
        }

        if (str_starts_with($path, 'api/')) {
            return 'api';
        }

        return 'web';
    }

    private function resolveRequestId(Request $request): ?string
    {
        $requestId = $request->headers->get('X-Request-Id')
            ?? $request->attributes->get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
