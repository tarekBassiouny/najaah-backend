<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'phone' => $this->faker->e164PhoneNumber(),
            'otp_code' => (string) rand(100000, 999999),
            'otp_token' => Str::uuid()->toString(),
            'provider' => 'sms',
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
            'user_id' => null,
        ];
    }
}
