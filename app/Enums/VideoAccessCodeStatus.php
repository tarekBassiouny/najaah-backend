<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoAccessCodeStatus: int
{
    case Active = 0;
    case Used = 1;
    case Revoked = 2;
    case Expired = 3;
}
