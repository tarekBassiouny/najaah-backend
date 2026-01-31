<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoLifecycleStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Ready = 2;
}
