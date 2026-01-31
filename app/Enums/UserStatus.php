<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: int
{
    case Inactive = 0;
    case Active = 1;
    case Banned = 2;
}
