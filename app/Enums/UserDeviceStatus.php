<?php

declare(strict_types=1);

namespace App\Enums;

enum UserDeviceStatus: int
{
    case Active = 0;
    case Revoked = 1;
    case Pending = 2;
}
