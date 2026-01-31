<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseStatus: int
{
    case Draft = 0;
    case Published = 3;
}
