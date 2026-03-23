<?php

declare(strict_types=1);

namespace App\Enums;

enum ParentLinkStatus: int
{
    case Active = 0;
    case PendingApproval = 1;
    case Revoked = 2;
}
