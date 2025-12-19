<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Support\Str;

class OtpService implements OtpServiceInterface
{
    /**
     * @return array{token: string}
     */
    public function send(string $phone, string $countryCode): array
    {
        $token = Str::uuid()->toString();
        $user = User::where('phone', $phone)
            ->where('country_code', $countryCode)->first();

        OtpCode::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'country_code' => $countryCode,
            'otp_code' => (string) rand(100000, 999999),
            'otp_token' => $token,
            'provider' => 'sms',
            'expires_at' => now()->addMinutes(5),
        ]);

        return [
            'token' => $token,
        ];
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
