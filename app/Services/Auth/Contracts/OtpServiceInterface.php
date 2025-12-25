<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\OtpCode;

interface OtpServiceInterface
{
    public function send(string $phone, string $countryCode): string;

    public function verify(string $otp, string $token): ?OtpCode;
}
