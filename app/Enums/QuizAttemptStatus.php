<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizAttemptStatus: int
{
    case InProgress = 0;
    case Submitted = 1;
    case TimedOut = 2;
    case Graded = 3;

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Submitted => 'Submitted',
            self::TimedOut => 'Timed Out',
            self::Graded => 'Graded',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Submitted, self::TimedOut, self::Graded], true);
    }

    public function canBeGraded(): bool
    {
        return in_array($this, [self::Submitted, self::TimedOut], true);
    }
}
