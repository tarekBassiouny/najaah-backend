<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface WebAuthServiceInterface
{
    /**
     * Send OTP for web login (student or parent).
     */
    public function sendOtp(string $phone, string $countryCode, ?int $centerId = null, bool $isParent = false): string;

    /**
     * Verify OTP and return the OTP record.
     */
    public function verifyOtp(string $otp, string $token): ?\App\Models\OtpCode;

    /**
     * Resolve or create a parent user, set is_parent=true, auto-link by phone match.
     *
     * @return array{user: User, auto_linked: bool}
     */
    public function resolveOrRegisterParent(string $phone, string $countryCode, ?int $centerId): array;
}
