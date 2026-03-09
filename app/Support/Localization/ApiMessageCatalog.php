<?php

declare(strict_types=1);

namespace App\Support\Localization;

class ApiMessageCatalog
{
    /**
     * @param  array<string, string|int|float>  $replace
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        /** @var array<string, array<string, string>> $catalog */
        $catalog = config('api_messages.catalog', []);

        $resolvedLocale = self::resolveLocale($locale);
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        $message = data_get($catalog, sprintf('%s.%s', $resolvedLocale, $key));
        if (! is_string($message) || $message === '') {
            $message = data_get($catalog, sprintf('%s.%s', $fallbackLocale, $key));
        }

        if (! is_string($message) || $message === '') {
            return $key;
        }

        foreach ($replace as $name => $value) {
            $message = str_replace(':'.$name, (string) $value, $message);
        }

        return $message;
    }

    public static function translateLiteral(string $message, ?string $locale = null): string
    {
        /** @var array<string, string> $reverse */
        $reverse = config('api_messages.reverse_en_to_key', []);
        $key = $reverse[$message] ?? null;

        if (is_string($key) && $key !== '') {
            return self::get($key, locale: $locale);
        }

        return self::translatePattern($message, $locale);
    }

    private static function translatePattern(string $message, ?string $locale = null): string
    {
        $resolved = self::resolveLocale($locale);
        if ($resolved !== 'ar') {
            return $message;
        }

        if (preg_match('/^Only students can access (.+)\\.$/', $message, $matches) === 1) {
            return 'هذه الواجهة متاحة للطلاب فقط للوصول إلى '.trim($matches[1]).'.';
        }

        if (preg_match('/^(.+) not found\\.$/', $message, $matches) === 1) {
            return trim($matches[1]).' غير موجود.';
        }

        if (preg_match('/^(.+) retrieved successfully$/', $message, $matches) === 1) {
            return 'تم جلب '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) created successfully$/', $message, $matches) === 1) {
            return 'تم إنشاء '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) updated successfully$/', $message, $matches) === 1) {
            return 'تم تحديث '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) deleted successfully$/', $message, $matches) === 1) {
            return 'تم حذف '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) submitted successfully\\.?$/', $message, $matches) === 1) {
            return 'تم إرسال '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) approved successfully\\.?$/', $message, $matches) === 1) {
            return 'تمت الموافقة على '.trim($matches[1]).' بنجاح.';
        }

        if (preg_match('/^(.+) rejected successfully\\.?$/', $message, $matches) === 1) {
            return 'تم رفض '.trim($matches[1]).' بنجاح.';
        }

        return $message;
    }

    private static function resolveLocale(?string $locale): string
    {
        $allowed = ['en', 'ar'];
        $candidate = strtolower(trim($locale ?? (string) app()->getLocale()));

        return in_array($candidate, $allowed, true) ? $candidate : 'en';
    }
}
