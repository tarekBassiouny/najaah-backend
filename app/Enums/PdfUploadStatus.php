<?php

declare(strict_types=1);

namespace App\Enums;

enum PdfUploadStatus: int
{
    case Pending = 0;
    case Uploading = 1;
    case Ready = 2;
    case Failed = 3;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Uploading => 'Uploading',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }
}
