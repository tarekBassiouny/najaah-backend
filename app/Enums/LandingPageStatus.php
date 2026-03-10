<?php

declare(strict_types=1);

namespace App\Enums;

enum LandingPageStatus: int
{
    case Draft = 0;
    case Published = 1;
}
