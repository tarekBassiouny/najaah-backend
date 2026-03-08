<?php

declare(strict_types=1);

namespace App\Services\Timezone;

use App\Models\Center;
use App\Models\SystemSetting;
use App\Services\Timezone\Contracts\TimezoneServiceInterface;
use Carbon\Carbon;
use DateTimeZone;

class TimezoneService implements TimezoneServiceInterface
{
    private const DEFAULT_TIMEZONE = 'UTC';

    /**
     * Common timezones for the Middle East region.
     *
     * @var array<string, string>
     */
    private const COMMON_TIMEZONES = [
        'UTC' => 'UTC (Coordinated Universal Time)',
        'Africa/Cairo' => 'Africa/Cairo (Egypt)',
        'Asia/Riyadh' => 'Asia/Riyadh (Saudi Arabia)',
        'Asia/Dubai' => 'Asia/Dubai (UAE)',
        'Asia/Kuwait' => 'Asia/Kuwait (Kuwait)',
        'Asia/Qatar' => 'Asia/Qatar (Qatar)',
        'Asia/Bahrain' => 'Asia/Bahrain (Bahrain)',
        'Asia/Amman' => 'Asia/Amman (Jordan)',
        'Asia/Beirut' => 'Asia/Beirut (Lebanon)',
        'Asia/Jerusalem' => 'Asia/Jerusalem (Palestine)',
        'Africa/Tripoli' => 'Africa/Tripoli (Libya)',
        'Africa/Tunis' => 'Africa/Tunis (Tunisia)',
        'Africa/Algiers' => 'Africa/Algiers (Algeria)',
        'Africa/Casablanca' => 'Africa/Casablanca (Morocco)',
        'Europe/Istanbul' => 'Europe/Istanbul (Turkey)',
    ];

    public function getSystemTimezone(): string
    {
        $setting = SystemSetting::where('key', 'timezone')->first();

        if ($setting === null) {
            return self::DEFAULT_TIMEZONE;
        }

        $timezone = $setting->value['timezone'] ?? null;

        return is_string($timezone) && $this->isValidTimezone($timezone)
            ? $timezone
            : self::DEFAULT_TIMEZONE;
    }

    public function getCenterTimezone(Center $center): string
    {
        return $center->getEffectiveTimezone();
    }

    public function resolveTimezone(?Center $center = null): string
    {
        if ($center !== null) {
            return $this->getCenterTimezone($center);
        }

        return $this->getSystemTimezone();
    }

    public function formatDateTime(Carbon $date, ?string $timezone = null): string
    {
        $tz = $timezone ?? $this->getSystemTimezone();

        return $date->copy()->setTimezone($tz)->toIso8601String();
    }

    public function formatDate(Carbon $date, ?string $timezone = null): string
    {
        $tz = $timezone ?? $this->getSystemTimezone();

        return $date->copy()->setTimezone($tz)->toDateString();
    }

    public function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true);
    }

    /**
     * @return array<string, string>
     */
    public function getCommonTimezones(): array
    {
        return self::COMMON_TIMEZONES;
    }
}
