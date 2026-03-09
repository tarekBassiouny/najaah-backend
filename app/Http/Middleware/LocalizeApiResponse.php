<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization\ApiMessageCatalog;
use App\Support\Localization\ApiValidationMessageLocalizer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizeApiResponse
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);
        if (! is_array($payload)) {
            return $response;
        }

        $locale = (string) $request->attributes->get('locale', app()->getLocale());

        if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
            $payload['message'] = ApiMessageCatalog::translateLiteral($payload['message'], $locale);
        }

        if (
            isset($payload['error']) && is_array($payload['error'])
            && isset($payload['error']['message']) && is_string($payload['error']['message'])
            && $payload['error']['message'] !== ''
        ) {
            $payload['error']['message'] = ApiMessageCatalog::translateLiteral($payload['error']['message'], $locale);
        }

        if (isset($payload['errors']) && is_array($payload['errors'])) {
            $payload['errors'] = ApiValidationMessageLocalizer::localizeMixed($payload['errors'], $locale);
        }

        if (
            isset($payload['error']) && is_array($payload['error'])
            && isset($payload['error']['details']) && is_array($payload['error']['details'])
        ) {
            $payload['error']['details'] = ApiValidationMessageLocalizer::localizeMixed($payload['error']['details'], $locale);
        }

        return response()->json($payload, $response->getStatusCode(), $response->headers->all());
    }
}
