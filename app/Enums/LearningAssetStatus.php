<?php

declare(strict_types=1);

namespace App\Enums;

enum LearningAssetStatus: int
{
    case Draft = 0;
    case Published = 1;
    case Archived = 2;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
