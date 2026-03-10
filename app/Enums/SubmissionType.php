<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionType: int
{
    case File = 0;
    case Text = 1;
    case Link = 2;

    public function label(): string
    {
        return match ($this) {
            self::File => 'File Upload',
            self::Text => 'Text Response',
            self::Link => 'Link Submission',
        };
    }
}
