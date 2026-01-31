<?php

declare(strict_types=1);

namespace App\Enums;

enum DeviceChangeRequestSource: string
{
    case Mobile = 'MOBILE';
    case Otp = 'OTP';
    case Admin = 'ADMIN';
}
