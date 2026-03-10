<?php

declare(strict_types=1);

namespace App\Enums;

enum AttemptScorePolicy: int
{
    case Best = 0;
    case Latest = 1;
    case Average = 2;

    public function label(): string
    {
        return match ($this) {
            self::Best => 'Best Score',
            self::Latest => 'Latest Score',
            self::Average => 'Average Score',
        };
    }
}
