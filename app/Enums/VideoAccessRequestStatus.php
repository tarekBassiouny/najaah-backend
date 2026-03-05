<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoAccessRequestStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
}
