<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaSourceType: int
{
    case Unknown = 0;
    case Upload = 1;
}
