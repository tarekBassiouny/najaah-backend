<?php

declare(strict_types=1);

namespace App\Enums;

enum LearningAssetProgressStatus: int
{
    case InProgress = 0;
    case Completed = 1;

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    public function apiValue(): string
    {
        return match ($this) {
            self::InProgress => 'in_progress',
            self::Completed => 'completed',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
