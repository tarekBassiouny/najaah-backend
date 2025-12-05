<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $phone
 * @property string $otp_code
 * @property string $otp_token
 * @property string $provider
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $consumed_at
 * @property string|null $otp
 * @property string|null $token
 * @property-read User|null $user
 */
class OtpCode extends Model
{
    /** @use HasFactory<\Database\Factories\OtpCodeFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'provider' => 'sms',
    ];

    protected $fillable = [
        'phone',
        'country_code',
        'otp_code',
        'otp_token',
        'otp',
        'token',
        'provider',
        'expires_at',
        'consumed_at',
        'user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setOtpAttribute(string $otp): void
    {
        $this->attributes['otp_code'] = $otp;
    }

    public function getOtpAttribute(): ?string
    {
        /** @var string|null $otp */
        $otp = $this->attributes['otp_code'] ?? null;

        return $otp;
    }

    public function setTokenAttribute(string $token): void
    {
        $this->attributes['otp_token'] = $token;
    }

    public function getTokenAttribute(): ?string
    {
        /** @var string|null $token */
        $token = $this->attributes['otp_token'] ?? null;

        return $token;
    }

    protected static function booted(): void
    {
        static::creating(function (OtpCode $otp): void {
            if ($otp->provider === null) {
                $otp->provider = 'sms';
            }

            if ($otp->expires_at === null) {
                $otp->expires_at = now()->addMinutes(5);
            }
        });
    }
}
