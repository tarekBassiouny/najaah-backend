<?php

declare(strict_types=1);

namespace App\Enums;

enum AIContentSourceType: string
{
    case Video = 'video';
    case Pdf = 'pdf';
    case Section = 'section';
    case Course = 'course';
}
