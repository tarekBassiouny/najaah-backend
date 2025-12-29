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

    public function send(string $phone, string $countryCode): string
    {
        $token = Str::uuid()->toString();
        $otpCode = (string) random_int(100000, 100000);
        $user = User::where('phone', $phone)
            ->where('country_code', $countryCode)->first();

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
