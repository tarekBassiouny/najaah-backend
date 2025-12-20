<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse|JsonResponse|Response
    {
        $requestId = $this->resolveRequestId($request);
        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);

        try {
            $response = $next($request);
        } catch (\Throwable $throwable) {
            /** @var ExceptionHandler $handler */
            $handler = app(ExceptionHandler::class);
            $handler->report($throwable);
            $response = $handler->render($request, $throwable);
        }

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->header('X-Request-Id')
            ?? $request->header('X-Request-ID')
            ?? $request->header('x-request-id');

        if (! is_string($requestId) || $requestId === '') {
            $requestId = (string) Str::uuid();
        }

        return $requestId;
    }
}
