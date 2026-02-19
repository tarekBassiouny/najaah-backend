<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse|JsonResponse|Response
    {
        $requestId = $this->resolveRequestId($request);
        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);
        $startedAt = microtime(true);
        $shouldLog = $this->shouldLogRequest($request);

        try {
            $response = $next($request);
        } catch (\Throwable $throwable) {
            /** @var ExceptionHandler $handler */
            $handler = app(ExceptionHandler::class);
            $handler->report($throwable);
            $response = $handler->render($request, $throwable);
        }

        $response->headers->set('X-Request-Id', $requestId);

        if ($shouldLog) {
            $this->logRequestCompleted($request, $response, $requestId, $startedAt);
        }

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->header('X-Request-Id')
            ?? $request->header('X-Request-ID')
            ?? $request->header('x-request-id');

        if (! is_string($requestId) || ! $this->isValidRequestId($requestId)) {
            return (string) Str::uuid();
        }

        return trim($requestId);
    }

    private function isValidRequestId(string $requestId): bool
    {
        $value = trim($requestId);
        if ($value === '' || strlen($value) > 128) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9._:-]+$/', $value) === 1;
    }

    private function shouldLogRequest(Request $request): bool
    {
        if (! (bool) config('logging.request_logging.enabled', true)) {
            return false;
        }

        /** @var array<int, mixed> $excludedPaths */
        $excludedPaths = config('logging.request_logging.exclude_paths', []);

        foreach ($excludedPaths as $excludedPath) {
            if (is_string($excludedPath) && $excludedPath !== '' && $request->is($excludedPath)) {
                return false;
            }
        }

        return true;
    }

    private function logRequestCompleted(
        Request $request,
        SymfonyResponse|JsonResponse|Response $response,
        string $requestId,
        float $startedAt
    ): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $statusCode = $response->getStatusCode();
        $slowThresholdMs = (int) config('logging.request_logging.slow_ms', 1500);
        $channel = (string) config('logging.request_logging.channel', 'requests');
        $logLevel = $statusCode >= 500
            ? 'error'
            : ($durationMs >= $slowThresholdMs ? 'warning' : 'info');

        $route = $request->route();
        $user = $request->user();
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => is_object($route) && method_exists($route, 'getName') ? $route->getName() : null,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_size_bytes' => $this->resolveResponseSize($response),
        ];

        if ($user instanceof User) {
            $context['user_id'] = $user->id;
            $context['center_id'] = $user->center_id;
            $context['is_student'] = $user->is_student;
        }

        Log::channel($channel)->log($logLevel, 'request_completed', $context);
    }

    private function resolveResponseSize(\Symfony\Component\HttpFoundation\Response $response): ?int
    {
        $headerValue = $response->headers->get('Content-Length');
        if (is_string($headerValue) && is_numeric($headerValue)) {
            return (int) $headerValue;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return null;
        }

        return strlen($content);
    }
}
