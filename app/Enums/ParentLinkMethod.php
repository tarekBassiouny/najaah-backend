<?php

declare(strict_types=1);

namespace App\Enums;

enum ParentLinkMethod: int
{
    case AdminManaged = 0;
    case AutoMatched = 1;
    case ParentRequested = 2;
}
