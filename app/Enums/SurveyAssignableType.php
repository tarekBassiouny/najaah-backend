<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Center;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;

enum SurveyAssignableType: string
{
    case Center = 'center';
    case Course = 'course';
    case Section = 'section';
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
            self::Section => Section::class,
            self::Video => Video::class,
            self::User => User::class,
            self::All => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Center => 'Center',
            self::Course => 'Course',
            self::Section => 'Section',
            self::Video => 'Video',
            self::User => 'Student',
            self::All => 'All Students',
        };
    }
}
