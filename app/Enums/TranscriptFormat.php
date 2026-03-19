<?php

declare(strict_types=1);

namespace App\Enums;

enum TranscriptFormat: string
{
    case Txt = 'txt';
    case Vtt = 'vtt';
    case Srt = 'srt';
}
