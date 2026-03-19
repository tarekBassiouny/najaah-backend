<?php

declare(strict_types=1);

namespace App\Enums;

enum TranscriptSource: string
{
    case Manual = 'manual';
    case Auto = 'auto';
}
