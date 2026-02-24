<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NormalizeAdminApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldNormalize($request, $response)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $payload = $response instanceof JsonResponse ? $response->getData(true) : [];
        $payload = is_array($payload) ? $payload : [];

        if ($status >= 400) {
            $normalized = $this->normalizeErrorPayload($payload, $status);

            return response()->json($normalized, $status, $response->headers->all());
        }

        $normalized = $this->normalizeSuccessPayload($payload, $status, $request);
        $normalizedStatus = $status === 204 ? 200 : $status;

        return response()->json($normalized, $normalizedStatus, $response->headers->all());
    }

    private function shouldNormalize(Request $request, Response $response): bool
    {
        if (! $request->is('api/v1/admin/*')) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeSuccessPayload(array $payload, int $status, Request $request): array
    {
        $message = $this->extractSuccessMessage($payload, $status, $request);

        $payload['success'] = true;
        $payload['message'] = $message;

        if (! array_key_exists('data', $payload)) {
            $data = $payload;
            unset($data['success'], $data['message']);
            $payload['data'] = $data === [] ? null : $data;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeErrorPayload(array $payload, int $status): array
    {
        $message = $this->extractErrorMessage($payload, $status);
        $code = $this->extractErrorCode($payload, $status);
        $errors = $this->extractErrorDetails($payload);

        $payload['success'] = false;
        $payload['message'] = $message;
        $payload['code'] = $code;
        $payload['errors'] = $errors;
        $payload['data'] = null;

        if (! isset($payload['error']) || ! is_array($payload['error'])) {
            $payload['error'] = [];
        }

        $payload['error']['code'] = $code;
        $payload['error']['message'] = $message;
        $payload['error']['details'] = $errors;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractSuccessMessage(array $payload, int $status, Request $request): string
    {
        if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
            return $payload['message'];
        }

        return match (true) {
            $status === 201 => 'Created successfully.',
            $request->isMethod('delete') => 'Deleted successfully.',
            $request->isMethod('patch') || $request->isMethod('put') => 'Updated successfully.',
            default => 'Request completed successfully.',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractErrorMessage(array $payload, int $status): string
    {
        if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
            return $payload['message'];
        }

        if (isset($payload['error']) && is_array($payload['error']) && isset($payload['error']['message']) && is_string($payload['error']['message'])) {
            return $payload['error']['message'];
        }

        return match ($status) {
            400 => 'Bad request.',
            401 => 'Unauthenticated.',
            403 => 'Forbidden.',
            404 => 'Resource not found.',
            409 => 'Conflict.',
            422 => 'Validation failed.',
            default => 'Request failed.',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractErrorCode(array $payload, int $status): string
    {
        if (isset($payload['code']) && is_string($payload['code']) && $payload['code'] !== '') {
            return $payload['code'];
        }

        if (isset($payload['error']) && is_array($payload['error']) && isset($payload['error']['code']) && is_string($payload['error']['code'])) {
            return $payload['error']['code'];
        }

        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            default => 'REQUEST_FAILED',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function extractErrorDetails(array $payload): ?array
    {
        if (isset($payload['errors']) && is_array($payload['errors'])) {
            return $payload['errors'];
        }

        if (isset($payload['error']) && is_array($payload['error']) && isset($payload['error']['details']) && is_array($payload['error']['details'])) {
            return $payload['error']['details'];
        }

        return null;
    }
}
