<?php

declare(strict_types=1);

namespace App\Enums;

enum BulkItemStatus: int
{
    case Pending = 0;
    case Sent = 1;
    case Failed = 2;
}
