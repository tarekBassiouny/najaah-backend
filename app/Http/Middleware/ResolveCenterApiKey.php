<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Center;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCenterApiKey
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $apiKey = $request->header('X-Api-Key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return $this->deny();
        }

        $apiKey = trim($apiKey);
        $systemKey = (string) config('services.system_api_key', '');

        if ($systemKey !== '' && hash_equals($systemKey, $apiKey)) {
            $request->attributes->set('resolved_center_id', null);

            return $next($request);
        }

        $center = Center::query()
            ->where('api_key', $apiKey)
            ->first();

        if (! $center instanceof Center) {
            return $this->deny();
        }

        $request->attributes->set('resolved_center_id', (int) $center->id);

        return $next($request);
    }

    private function deny(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INVALID_API_KEY',
                'message' => 'Invalid API key.',
            ],
        ], 401);
    }
}
