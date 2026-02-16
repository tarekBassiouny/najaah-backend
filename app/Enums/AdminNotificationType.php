<?php

declare(strict_types=1);

namespace App\Enums;

enum AdminNotificationType: int
{
    case SYSTEM_ALERT = 1;
    case DEVICE_CHANGE_REQUEST = 2;
    case EXTRA_VIEW_REQUEST = 3;
    case SURVEY_RESPONSE = 4;
    case NEW_ENROLLMENT = 5;
    case CENTER_ONBOARDING = 6;

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM_ALERT => 'System Alert',
            self::DEVICE_CHANGE_REQUEST => 'Device Change Request',
            self::EXTRA_VIEW_REQUEST => 'Extra View Request',
            self::SURVEY_RESPONSE => 'Survey Response',
            self::NEW_ENROLLMENT => 'New Enrollment',
            self::CENTER_ONBOARDING => 'Center Onboarding',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SYSTEM_ALERT => 'alert-circle',
            self::DEVICE_CHANGE_REQUEST => 'smartphone',
            self::EXTRA_VIEW_REQUEST => 'eye',
            self::SURVEY_RESPONSE => 'clipboard-check',
            self::NEW_ENROLLMENT => 'user-plus',
            self::CENTER_ONBOARDING => 'building',
        };
    }

    /**
     * @return array<string, string>
     */
    public function labelTranslations(): array
    {
        return match ($this) {
            self::SYSTEM_ALERT => ['en' => 'System Alert', 'ar' => 'تنبيه النظام'],
            self::DEVICE_CHANGE_REQUEST => ['en' => 'Device Change Request', 'ar' => 'طلب تغيير الجهاز'],
            self::EXTRA_VIEW_REQUEST => ['en' => 'Extra View Request', 'ar' => 'طلب مشاهدات إضافية'],
            self::SURVEY_RESPONSE => ['en' => 'Survey Response', 'ar' => 'رد على الاستبيان'],
            self::NEW_ENROLLMENT => ['en' => 'New Enrollment', 'ar' => 'تسجيل جديد'],
            self::CENTER_ONBOARDING => ['en' => 'Center Onboarding', 'ar' => 'انضمام مركز'],
        };
    }
}
