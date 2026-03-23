<?php

declare(strict_types=1);

namespace App\Enums;

enum DeviceType: string
{
    case Mobile = 'mobile';
    case Web = 'web';
}
