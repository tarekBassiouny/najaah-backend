<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseStatus: int
{
    case Draft = 0;
    case Uploading = 1;
    case Ready = 2;
    case Published = 3;
    case Archived = 4;
}
