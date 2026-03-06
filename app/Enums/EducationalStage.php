<?php

declare(strict_types=1);

namespace App\Enums;

enum EducationalStage: int
{
    case Elementary = 0;
    case Middle = 1;
    case HighSchool = 2;
    case University = 3;
    case Graduate = 4;

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Middle => 'Middle',
            self::HighSchool => 'High School',
            self::University => 'University',
            self::Graduate => 'Graduate',
        };
    }
}
