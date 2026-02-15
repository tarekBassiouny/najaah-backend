<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;

enum SurveyAssignableType: string
{
    case Center = 'center';
    case Course = 'course';
    case Video = 'video';
    case User = 'user';
    case All = 'all';

    /**
     * @return class-string|null
     */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::Center => Center::class,
            self::Course => Course::class,
            self::Video => Video::class,
            self::User => User::class,
            self::All => User::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Center => 'Center',
            self::Course => 'Course',
            self::Video => 'Video',
            self::User => 'Student',
            self::All => 'All Students',
        };
    }
}
