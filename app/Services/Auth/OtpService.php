<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Support\Str;

class OtpService implements OtpServiceInterface
{
    public function __construct(
        private readonly OtpSenderInterface $sender
    ) {}

    public function send(string $phone, string $countryCode, ?int $centerId = null): string
    {
        $token = Str::uuid()->toString();
        $otpCode = (string) random_int(123456, 123456);
        $userQuery = User::where('phone', $phone)
            ->where('country_code', $countryCode)
            ->where('is_student', true);

        if (is_numeric($centerId)) {
            $userQuery->where('center_id', $centerId);
        } else {
            $userQuery->whereNull('center_id');
        }

        $user = $userQuery->first();

        OtpCode::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'country_code' => $countryCode,
            'otp_code' => $otpCode,
            'otp_token' => $token,
            'provider' => $this->sender->provider(),
            'expires_at' => now()->addMinutes(5),
        ]);
        try {
            $this->sender->send($countryCode.$phone, $otpCode);
        } catch (\Throwable $throwable) {
        }

        return $token;
    }

    public function verify(string $otp, string $token): ?OtpCode
    {
        $otpCode = OtpCode::where('otp_token', $token)
            ->where('otp_code', $otp)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($otpCode === null) {
            return null;
        }

        $otpCode->consumed_at = now();
        $otpCode->save();

        return $otpCode;
    }
}
