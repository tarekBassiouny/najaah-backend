<?php

declare(strict_types=1);

namespace App\Enums;

enum EnrollmentStatus: int
{
    case Active = 0;
    case Deactivated = 1;
    case Cancelled = 2;
    case Pending = 3;
}
