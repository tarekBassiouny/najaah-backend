<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\OtpCode;

interface OtpServiceInterface
{
    /**
     * @return array{token: string}
     */
    public function send(string $phone, string $countryCode): array;

    public function verify(string $otp, string $token): ?OtpCode;
}
