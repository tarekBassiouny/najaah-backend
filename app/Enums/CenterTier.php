<?php

declare(strict_types=1);

namespace App\Enums;

enum CenterTier: int
{
    case Standard = 0;
    case Premium = 1;
    case Vip = 2;
}
