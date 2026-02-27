<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaSourceType: int
{
    case Url = 0;
    case Upload = 1;
}
