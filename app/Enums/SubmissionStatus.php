<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionStatus: int
{
    case Draft = 0;
    case Submitted = 1;
    case Graded = 2;
    case Returned = 3;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Graded => 'Graded',
            self::Returned => 'Returned for Revision',
        };
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    public function canBeGraded(): bool
    {
        return $this === self::Submitted;
    }

    public function isCompleted(): bool
    {
        return $this === self::Graded;
    }
}
