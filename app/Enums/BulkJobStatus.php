<?php

declare(strict_types=1);

namespace App\Enums;

enum BulkJobStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Completed = 2;
    case Paused = 3;
    case Failed = 4;
    case Cancelled = 5;
}
