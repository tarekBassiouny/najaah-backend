<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseAccessModel: string
{
    case Enrollment = 'enrollment';
    case VideoCode = 'video_code';
}
