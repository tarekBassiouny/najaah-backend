<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoCodeBatchStatus: int
{
    case Open = 0;
    case Closed = 1;
}
