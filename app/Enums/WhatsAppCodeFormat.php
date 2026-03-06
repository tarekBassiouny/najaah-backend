<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsAppCodeFormat: string
{
    case QrCode = 'qr_code';
    case TextCode = 'text_code';
}
