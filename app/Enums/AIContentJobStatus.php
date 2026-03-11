<?php

declare(strict_types=1);

namespace App\Enums;

enum AIContentJobStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Completed = 2;
    case Failed = 3;
    case Approved = 4;
    case Published = 5;
    case Discarded = 6;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Discarded => 'Discarded',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Failed, self::Published, self::Discarded], true);
    }
}
