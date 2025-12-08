<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetRequestLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $allowed = ['en', 'ar'];

        $headerLocale = $this->normalizeLocale((string) $request->header('X-Locale', ''));
        if ($headerLocale !== null && in_array($headerLocale, $allowed, true)) {
            return $headerLocale;
        }

        $acceptLanguage = $this->normalizeLocale((string) $request->header('Accept-Language', ''));
        if ($acceptLanguage !== null && in_array($acceptLanguage, $allowed, true)) {
            return $acceptLanguage;
        }

        $default = config('app.locale', 'en');

        return in_array($default, $allowed, true) ? $default : 'en';
    }

    private function normalizeLocale(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $primary = explode(',', $trimmed)[0] ?? '';
        $primary = explode(';', $primary)[0] ?? '';
        $primary = explode('-', $primary)[0] ?? $primary;
        $primary = strtolower(trim($primary));

        return $primary !== '' ? $primary : null;
    }
}
